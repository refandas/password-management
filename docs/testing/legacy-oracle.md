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

## Comparison recorded 2026-10-23 (ticket #11)

The Vault Account read path — the list (`GET /account` and the sidebar's
`GET /account/index`) and the detail page — asserted by the acceptance suite
on both stacks (24/24 green on each, identical fixture):

| Check on the oracle (:8001)                          | Result |
| ---------------------------------------------------- | ------ |
| Acceptance suite (all seven test files, incl. Account) | 24/24 green |
| `GET /account` page bytes (logged in as Jack)        | byte-identical modulo CI4 toolbar markers and view-boundary newlines (the same noise class as the ticket #10 pages) |
| `GET /account/detail_account/1` page bytes           | byte-identical (same) |
| List row order (the join carries no ORDER BY)        | account id order — Instagram, Twitter, Amazon, Steam cards on both stacks |
| Anonymous `GET /account/detail_account/1`            | 200 with Jack's decrypted plaintexts — CI3's detail_account() checked no session and no ownership; the parity hole is kept (like #10's ungated activation anchors) |
| `GET /account/detail_account/999` (no row)           | 200 + empty form on both stacks; the oracle additionally prints inline PHP notices (its dev-mode display_errors) — not replicated |

Run method: same as ticket #10 — both web containers joined the `parity`
docker network, then
`docker compose exec -e ACCEPTANCE_BASE_URL=http://password-management-legacy web php vendor/bin/phpunit --no-coverage tests/Acceptance`.

The decryption is the headline: the ported routine
(`app/Libraries/LegacyEncryption.php`) reads rows the oracle's own CI3
`Encryption` library provably decrypts (see the ticket #8 record) to the
identical plaintexts through the HTTP seam — list and detail pages, every
seeded account.

## Comparison recorded 2026-10-24 (ticket #12)

The Vault Account write path — the add form (`GET /account/add_account`),
add (`POST /account/add`), update (`POST /account/update/{id}`), delete
(`GET /account/delete/{id}`) — asserted by the acceptance suite on both
stacks (30/30 green on each, identical fixture):

| Check on the oracle (:8003)                          | Result |
| ---------------------------------------------------- | ------ |
| Acceptance suite (all seven files, incl. AccountWrite) | 30/30 green |
| `GET /account/add_account` page bytes (Jack logged in) | byte-identical modulo CI4 toolbar markers and view-boundary newlines (the established noise classes) |
| `GET /account` list bytes (Jack logged in)            | byte-identical (same noise classes) |
| Anonymous `POST /account/add`                        | 500 "Database Error" page, no row stored — the NOT NULL `id_user` rejects the null session User; the port answers its own dev error page with the same status and effect |
| Anonymous `POST /account/update/3`                   | 303 → `/account`, row rewritten — update and delete check no session (parity hole kept, like the ungated detail page) |
| `GET /account/delete/{id}` (any id, logged in or not) | 200, not a redirect — see below |

### The delete quirk's dev-mode observable

The oracle's delete answers a broken 200 — an inline "Illegal string
offset 'username_email'" warning (the accidental assignment,
`AccountModel` line 114) starts output before the redirect header fires
under dev-mode display_errors. The row is still deleted and the flash
still lands on the next request. That is the same dev-mode artifact class
ticket #11 ruled "not replicated": with errors off (the legacy production
setting), output never starts and the redirect stands. The port implements
the production observable — clean delete, redirect, byte-identical flash —
and the suite asserts the effects both stacks agree on (row gone, flash on
the next page, empty detail render), leaving the delete response's own
status unasserted.

Run method: same as tickets #10/#11 — both web containers on the `parity`
docker network, then
`docker compose exec -e ACCEPTANCE_BASE_URL=http://password-management-legacy web php vendor/bin/phpunit --no-coverage tests/Acceptance`.
(The oracle mapped to host port 8003 this round — 8001/8002 were taken by
unrelated host processes; the host port is cosmetic, the suite drives the
container by name. Note `run-acceptance.sh` recreates the port's web
container on rebuild, which drops its manual `parity` attachment — re-run
`docker network connect parity password-management` before the next
oracle comparison.)

## Comparison recorded 2026-10-25 (ticket #13)

User management + Access Level gating — the Users list
(`GET /dashboard/users`) and User detail pages (`GET /dashboard/user/2`,
`/3`, `/999`), plus the sidebar menu visibility per Access Level, asserted
by the acceptance suite on both stacks (35/35 green on each, identical
fixture):

| Check on the oracle (:8001)                                   | Result |
| ------------------------------------------------------------- | ------ |
| Acceptance suite (all nine test files, incl. UserManagement) | 35/35 green |
| `GET /dashboard/users` page bytes (Admin logged in)           | byte-identical modulo the established noise classes (CI4 toolbar markers, view-boundary newlines, whitespace) |
| `GET /dashboard/user/2` page bytes (Admin logged in)          | byte-identical (same noise classes) |
| Users list split (active / not active, Admin excluded)        | identical — Jack in User Active, Ghost in User Not Active, no Admin row, on both stacks |
| Detail branch per `registered` (deactivate vs. activate btn)  | identical — Indonesian status lines and button hrefs byte-identical |
| Menu visibility per Access Level                              | identical — level 1: USER shown, ACCOUNT hidden; level 2: the mirror |
| `GET /dashboard/user/999` (no row)                            | 200 + empty name + activation branch on both stacks; the oracle's PHP 7.3 reads the missing row's fields as null silently — the port keeps the same observable render through null-coalescing guards (the ticket #11 precedent; the oracle's inline notices are its dev-mode artifact, not replicated) |

This round's byte comparison caught one real divergence before it landed:
the port's first draft of `user.php` dropped `content-wrapper`'s closing
div — the legacy file's tail closes the div *before* the
`/.container-fluid` comment that names it, an easy placement to miss. The
port now reproduces each file's exact div and comment sequence.

Run method: same as the earlier tickets — both web containers on the
`parity` docker network, then
`docker compose exec -e ACCEPTANCE_BASE_URL=http://password-management-legacy web php vendor/bin/phpunit --no-coverage tests/Acceptance`
(the oracle mapped to host port 8001 this round).


