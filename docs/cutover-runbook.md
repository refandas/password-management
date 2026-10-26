# Cutover runbook

The maintenance window that swaps the running stack from the legacy CI3 app
(`pre-migration` branch) to the ported CI4 app, re-encrypting every Vault
Account credential once (spec #1, ADR-0002, ADR-0003). Downtime is accepted
and sessions are ephemeral — every User logs in again after the window with
their existing credentials (portable bcrypt hashes, unchanged by the move).

The sequence inside the window, in order: **final data sync → run the
re-encryption command → run the full suite → swap the stack.** Every command
below runs on the production host, from the repo checkout that serves
production.

## What changes at cutover — and what must not

- **The web container** — rebuilt from the ported app's image; same host
  port (8000), same URLs (spec #1, user story 28).
- **Vault Account credentials** — the one-time re-encryption (`php spark
  vault:reencrypt`) rewrites each stored credential from the legacy CI3
  format to the CI4 encrypter's envelope: the same per-account key in the
  same `account.key_str` column, the same `username_email`/`password`
  columns (ADR-0003). No schema change, no key change, no plaintext change
  — only the ciphertext format.
- **The database service** — untouched. `pass-manag-db` (mysql:8.0,
  `password_manager`) is the production database; both branches' compose
  files define the service identically, so the new stack adopts the running
  container with its data. **Never `docker compose down` it** — that
  removes the container (the service defines no volume; the data lives in
  the container).
- **Users** — logged out by the swap (sessions live in the old container's
  writable directory); they log back in.

## Preconditions (before the window)

1. The migration branch is merged and the cutover commit is green:
   `./scripts/run-acceptance.sh` passes in CI or on a dev host.
2. Rehearse once on the production host with the isolated verification
   stack (below) — same build, same commands, zero production risk.
3. The production checkout is clean (`git status`), and users are notified
   of the window.

## The isolated verification stack

The acceptance suite **reseeds its database on every run** — it must never
run against the production database. `docker-compose.verify.yml` renames
and remaps both containers, so a verification run coexists with the
production stack on the same host:

```console
$ COMPOSE_FILE=docker-compose.yml:docker-compose.verify.yml \
    COMPOSE_PROJECT_NAME=cutover-verify \
    ./scripts/run-acceptance.sh
```

Teardown afterwards:

```console
$ COMPOSE_FILE=docker-compose.yml:docker-compose.verify.yml \
    COMPOSE_PROJECT_NAME=cutover-verify \
    docker compose down
```

## The maintenance window

### 1. Stop the legacy stack — writes cease

```console
$ docker compose stop web
```

The legacy app stops serving; `pass-manag-db` keeps running. Users mid-edit
lose nothing but their session (accepted, ADR-0002).

### 2. Final data sync — the backup dump

```console
$ docker exec pass-manag-db mysql -uroot -proot -N -B password_manager \
    -e "SELECT COUNT(*) FROM account"     # record this number
$ docker exec pass-manag-db mysqldump -uroot -proot password_manager \
    > password_manager-cutover-$(date +%Y%m%d%H%M).sql
```

This dump is both the rollback point and the final-sync artifact: with the
in-place deployment below (same checkout, same compose project, same
database container) no data moves — step 3 ends by re-running the count
command and checking the number is unchanged. If the new stack were
deployed with its own MySQL instead, restore this dump into it before
step 4.

### 3. Bring up the new stack

```console
$ git fetch && git checkout <migration-branch>
$ docker compose build web
$ docker compose up -d
```

The web service's definition changed (built image, framework docroot), so
compose recreates that container on port 8000; the db service's definition
is identical, so the running `pass-manag-db` container — and its data —
carries over untouched. Deploy in place like this rather than from a second
checkout: both branches pin the same container names, and a second project
would collide with them.

Re-run the count command from step 2 — the number must be identical.

### 4. Smoke checks

```console
$ docker compose exec web php spark migrate:status
```

Exit 0, `No migrations were found.` — the framework migration CLI, the
successor of the legacy CLI migration tool; the baseline schema is frozen
(spec #1), so there is nothing to run, but the tool is proven alive for
every schema change after the port.

```console
$ curl -sf http://localhost:8000/auth | grep -q 'Login - Password Management' \
    && echo 'login page answers'
```

### 5. Re-encrypt the vault

```console
$ docker compose exec web php spark vault:reencrypt --dry-run
$ docker compose exec web php spark vault:reencrypt
$ docker compose exec web php spark vault:reencrypt --verify
```

- The dry run lists every planned row (id and Media Type — never a
  plaintext) and reports `N Vault Account(s) would be re-encrypted`. On a
  clean cutover N is every Vault Account in the dump.
- The run rewrites each listed row through the app's own write path, all
  rows in one transaction: a mid-run failure rolls back and writes nothing.
  An unreadable row (neither routine decrypts it) aborts before anything
  is written.
- Verify must exit 0 with `All N Vault Account(s) verified.` — that is the
  go/no-go gate for the swap. `still legacy CI3 format` or
  `unreadable` lines name every row that failed.

Then one spot-check through the app itself: log in as a real User, open a
Vault Account's detail page, confirm the credential reads. The command is
idempotent — if anything went wrong mid-window it can simply be re-run.

### 6. Run the full suite — the isolated verification stack

The same command as the rehearsal (see above). This is the last gate on the
build: every parity surface of spec #1 plus the cutover proof itself, on the
exact build now serving production. The migrated data's gate is step 5 —
verify plus the spot-check — since the suite runs against its own seeded
fixture and never the production database. Tear the verification stack down
afterwards.

### 7. Swap complete

The window closes; users log in again with their existing credentials.
Keep the dump and the legacy branch checked out for the rollback window
(one week is a sane default), then retire them. Ticket #16 retired the
in-repo oracle tooling at cutover time; the branch and the dump are the
owner's to delete once the window closes.

## Rollback

If the window aborts before the swap is declared:

1. Stop the new web container — `docker compose stop web` — and keep
   `pass-manag-db` running.
2. If the re-encryption ran, restore the dump — the legacy app reads only
   the legacy format:

   ```console
   $ docker exec -i pass-manag-db mysql -uroot -proot password_manager \
       < password_manager-cutover-<timestamp>.sql
   ```

3. Bring the legacy app back: `git checkout pre-migration && docker compose
   up -d`. Compose reuses the legacy image already on the host — do not
   `--build` it; the legacy Dockerfile's `apt-get upgrade` layer no longer
   resolves (Debian stretch is archived).

Users log in again either way — sessions were dropped at step 1 of the
window.

## After the window

- Watch `docker compose logs web` for the first few days.
- ADR-0003's flagged weaknesses become the post-migration backlog as the
  spec publishes: the per-account key-storage redesign, the verified
  password reset, the mixed-language UI, and the rest — separate issues,
  out of cutover scope.
