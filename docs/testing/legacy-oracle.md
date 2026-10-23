# Legacy oracle

The oracle is the pre-migration app — CI3 on PHP 7.3, untouched — runnable
side-by-side with the new CI4 stack. Old and new receive identical requests;
outcomes (status, redirects, flash text, listed content, decrypted detail
values) are compared. Parity is proven against it on every ticket (spec #1).

The pre-migration state is branch `pre-migration` (develop HEAD before the
port began). Both stacks run at once on separate ports, each with its own
MySQL and an identical copy of the seed fixture.

## Start the oracle

```console
$ git worktree add ../password-management-legacy pre-migration

# Runtime wrapper: the legacy Dockerfile's `apt-get upgrade` layer is dead
# (Debian stretch archived). tests/oracle/Dockerfile.legacy rebuilds the same
# runtime without it — no application code changes.
$ docker build -t password-management-legacy:oracle \
    -f tests/oracle/Dockerfile.legacy ../password-management-legacy

$ cd ../password-management-legacy
$ docker compose -f docker-compose.yml -f - -p legacy-oracle up -d <<'YAML'
services:
  web:
    image: password-management-legacy:oracle
    container_name: password-management-legacy
    ports: !override
      - 8001:80
  db:
    container_name: pass-manag-db-legacy
    ports: !override
      - 6037:3306
YAML
```

`ports: !override` replaces the base file's ports instead of merging with
them (plain compose merging concatenates port lists, which would collide with
the new stack on 8000/6036).

Seed the oracle's database with the same fixture as the new stack:

```console
$ until docker exec pass-manag-db-legacy mysql -uroot -proot \
    -e 'SELECT 1' >/dev/null 2>&1; do sleep 2; done
$ docker exec -i pass-manag-db-legacy mysql -uroot -proot password_manager \
    < ../password-management/tests/seed/schema.sql
$ docker exec -i pass-manag-db-legacy mysql -uroot -proot password_manager \
    < ../password-management/tests/seed/seed.sql
```

Teardown:

```console
$ docker compose -p legacy-oracle down
$ git worktree remove ../password-management-legacy
```

## Comparison procedure

Send the same request to both stacks and compare status, Location, flash
text, and rendered content:

```console
$ curl -s http://localhost:8000/…   # new CI4 app
$ curl -s http://localhost:8001/…   # legacy oracle
```

Cookies are domain-scoped and port-agnostic, so one cookie jar (or the
acceptance suite pointed at either `ACCEPTANCE_BASE_URL`) works against both.

### Quirk: the oracle's redirects reference port 8000

The legacy `application/config/config.php` hardcodes
`base_url = 'http://localhost:8000/'`, and the CI4 `.env` deliberately sets
`app.baseURL` to the same value — old and new emit byte-identical Location
headers. That means the oracle running on 8001 still *points* its redirects
at 8000: don't blind-follow; compare the path (both stacks agree), and
re-request on whichever port the stack runs on.

## Comparison recorded 2026-10-22 (ticket #8)

What could be compared at tracer-bullet time, and what it proved:

| Check on the oracle (:8001)                        | Result |
| -------------------------------------------------- | ------ |
| `GET /` → login page (legacy default route)        | 200, `Login - Password Management` |
| `POST /auth/login` (jack@email.com / user1234)     | 303 → `/dashboard` — the seeded bcrypt hashes pass the legacy `password_verify` |
| `GET /account/detail_account/1`                    | renders `jack.sparrow@seamail.example` / `insta-pass-0123` — **real CI3 code decrypted the seed fixture to its known plaintexts** |

The encryption row is the important one: the seed fixture is provably
legacy-format, verified by the frozen branch's own `Encryption` library on
PHP 7.3, not only by the format port in `tests/tools/generate-seed.php`.

Old vs new on the same request — `GET /`: legacy answers the login page, the
new stack answers the CI4 welcome page. Not parity, as expected: no endpoint
has been ported yet. Per-endpoint byte comparison against the oracle begins
with the first ported endpoint (ticket 2) and is asserted by the acceptance
suite from then on.

## Comparison recorded 2026-10-23 (ticket #10)

Registration, activation, and forgot/reset — the whole ticket #10 surface,
asserted by the acceptance suite on both stacks (18/18 green on each,
identical fixture):

| Check on the oracle (:8001)                                             | Result |
| ----------------------------------------------------------------------- | ------ |
| Acceptance suite (all five files, incl. Registration/Activation/Forgot) | 18/18 green |
| `GET /auth/register` page bytes                                         | byte-identical to the new stack (only CI4 dev-toolbar markers differ) |
| `POST /auth/resetpassword` (existing email) — the set-password form     | byte-identical (same) |
| `POST /auth/create` → flash + 303 `/auth`                               | identical — including the quirk: `registered = 1` is inserted, so the "activate before login" message overstates and the fresh User logs in immediately |
| `GET /auth/activateuser/{id}` / `deactivateuser/{id}`                   | 307 `/dashboard/users`, Indonesian flash text byte-identical, `registered` flips — and the endpoints are ungated on both stacks (parity hole kept, post-migration backlog) |

Run method for this round: both web containers joined one docker network
(`docker network create parity`), then

```console
$ docker compose exec -e ACCEPTANCE_BASE_URL=http://password-management-legacy \
    web php vendor/bin/phpunit --no-coverage tests/Acceptance
```

— the suite drives the oracle by container name over port 80. The oracle's
Location headers still point at `localhost:8000` (hardcoded legacy
`base_url`), which is exactly what the assertions expect on both stacks.

