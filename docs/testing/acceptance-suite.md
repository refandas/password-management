# Acceptance suite

The acceptance suite drives the running app over plain HTTP — the one
pre-agreed seam (spec #1): docker-compose's HTTP boundary. It never boots the
framework in-process and never cuts into the codebase. It asserts external
behavior only: status codes, redirects, rendered content, flash message text,
and decrypted values on the Vault Account detail page. The companion seam is
the CLI boundary for CLI tools (spec #1's justified exception): `php spark`
subprocesses — the cutover re-encryption command and the framework migration
CLI — asserted on their reports and exit codes.

## Run it

```console
$ ./scripts/run-acceptance.sh
```

That builds the web image, starts the stack, waits for MySQL, loads
`tests/seed/schema.sql` + `tests/seed/seed.sql`, asserts the fixture landed
(3 Users, 4 Vault Accounts, 14 Media Types), and runs PHPUnit inside the web
container against `http://localhost` (Apache in the same container). It is
idempotent — the schema drops and recreates every table, so every run starts
from the same dataset.

Two phases, two loads of the fixture. The cutover proof
(`tests/Acceptance/VaultReencryptionTest.php`) runs first against the
pristine legacy-seeded fixture — the one-time re-encryption of every Vault
Account, proven through both seams (below). It leaves the table re-encrypted,
so the runner reloads the fixture before the parity suite, which starts from
the legacy-seeded rows exactly as every ticket's run did. The parity suite
then runs the proof a second time — the file sorts last, after every other
file has consumed the legacy fixture — so every run also proves the
command's order-robustness mid-suite: its counts are parsed, never assumed.

The runner asserts the database leg of the path and PHPUnit the HTTP leg: the
default route does not open the database yet, so the single end-to-end
assertion covers container → Apache → docroot → CI4, while the runner proves
container → MySQL → seeded fixture. Per-endpoint database assertions begin
with the first ported endpoint (ticket 2).

The full suite (skeleton unit tests + acceptance) — `ACCEPTANCE_BASE_URL`
matters: Apache listens on port 80 in the container; `8000` is only the host
mapping:

```console
$ docker compose exec -e ACCEPTANCE_BASE_URL=http://localhost web \
    php vendor/bin/phpunit --no-coverage
```

`--no-coverage`: the image ships no coverage driver and `phpunit.dist.xml`
runs with `failOnWarning="true"`.

Point the suite at a different stack with `ACCEPTANCE_BASE_URL` — that is how
it also drives the [legacy oracle](legacy-oracle.md).

## Fixture

- `tests/seed/plaintexts.php` — the single source of truth: one Admin User,
  one regular User, one unactivated User (`registered = 0`, for the login
  error state), Vault Accounts across several Media Types, with known
  plaintext credentials.
- `tests/tools/generate-seed.php` — generates the loadable
  `tests/seed/seed.sql` from the plaintexts. Deterministic: per-account keys
  and per-field IVs derive from the account id, so regeneration is
  byte-stable and the committed file can be diffed. Never hand-edit seed.sql.

```console
$ docker run --rm -v "$PWD:/app" -w /app php:8.5.12-apache \
    php tests/tools/generate-seed.php --check
```

`--check` fails unless the committed seed.sql matches a fresh generation
exactly and every ciphertext and password hash round-trips.

### Legacy encryption format

Vault Account credentials are seeded in the legacy CI3 format so the legacy
oracle and the ported app read the same rows. The canonical spec is the
header of `tests/tools/generate-seed.php` (ported verbatim from CI3 3.1.10's
`system/libraries/Encryption.php`, OpenSSL driver, always selected on PHP
7.3); in short:

```text
enc_key  = hkdf(key, sha512, zero-salt, 32, 'encryption')
auth_key = hkdf(key, sha512, zero-salt, 64, 'authentication')
payload  = base64(iv || aes-256-ctr(plaintext, enc_key, iv))
stored   = hex(hmac_sha512(payload, auth_key)) || payload
```

`key` is the raw 32 bytes of `hex2bin(account.key_str)`. The format is proven
against real CI3 code, not just this port: the oracle's detail page decrypts
the seeded rows to the plaintexts (see legacy-oracle.md). The ported app
reads the same format through `app/Libraries/LegacyEncryption.php` (ticket
#11) — the acceptance suite proves the two implementations agree on every
run, since the app must decrypt exactly what the generator encrypts.

### Write-path encryption (the CI4 encrypter)

Rows written through the port (ticket #12) are encrypted by CI4's
OpenSSLHandler with the row's per-account key passed per call — the same
per-account key scheme, the framework's encrypter (ADR-0003).
`app/Config/Encryption.php` keeps `$rawData = false` so the stored value is
the ASCII envelope (hex HMAC prefix + base64 payload) the frozen latin1
`varchar(256)` columns can hold. The CI4 envelope differs from the legacy
one above (empty HKDF key infos, not `encryption`/`authentication`), so
`AccountModel` reads each row with the legacy routine first and falls back
to the CI4 handler: seeded/pre-cutover rows and port-written rows coexist
until the cutover re-encryption retires the legacy format.

### The cutover proof (the re-encryption command)

`php spark vault:reencrypt` (`app/Commands/VaultReencrypt.php`, ticket #15)
is the one-time cutover command: every legacy-format Vault Account is
decrypted with the ported legacy routine and re-encrypted with the CI4
encrypter through the app's own write path — the same per-account key, the
same columns (ADR-0003), all rows in one transaction. Three modes:
`--dry-run` (report the plan, write nothing), the bare run, and `--verify`
(every credential must decrypt with the CI4 encrypter — the post-cutover
gate).

`tests/Acceptance/VaultReencryptionTest.php` proves it at both seams: the
CLI reports (dry-run plan, run summary, verify gate — parsed, so the test
holds from any fixture state with a legacy row left) and the HTTP detail
pages, which must render the same plaintexts before and after the run. The
dry run's no-write proof is verify: it still fails on every planned row
afterwards. The command is idempotent — the second run is a clean no-op.

`tests/Acceptance/MigrationCliSmokeTest.php` is the smoke check of the
framework migration CLI (`spark migrate:status`), the successor of the
legacy CLI migration tool — the suite's other CLI-seam check. The CLI
helper (`AcceptanceTestCase::spark()`) always runs this checkout's
framework CLI against its own database, whatever `ACCEPTANCE_BASE_URL`
points the HTTP requests at, so both files are safe in an oracle
comparison.

The maintenance window itself — final data sync, the command, the full
suite in an isolated stack, the swap — is the
[cutover runbook](../cutover-runbook.md).

### Known credentials

| User          | Email             | Password    | Access Level |
| ------------- | ----------------- | ----------- | ------------ |
| Admin         | admin@email.com   | admin1234   | 1 (admin)    |
| Jack (regular)| jack@email.com    | user1234    | 2 (user)     |
| Ghost (unactivated)| ghost@email.com | ghost1234 | 2 (user)    |

Vault Account plaintexts live in `tests/seed/plaintexts.php`; all four
belong to Jack.

## Configuration

All configuration comes from the framework's environment file: the committed
`env` template holds the docker-compose development values and the image bakes
it to `.env` at build. `app/Config/Database.php` contains placeholders only —
`database.default.*` in `.env` supplies hostname, database, username,
password, and driver, applied by CI4's config-to-env binding.
