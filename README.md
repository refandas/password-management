# password-management

A self-hosted password manager: Users store Vault Accounts (credentials for
other services) encrypted at rest, organized by Media Type.

CodeIgniter 4.7 on PHP 8.5.12 (spec #1) — ported 1:1 from the CI3/PHP 7.3
app: every URL, behavior, and UI string is the legacy app's, proven green
old-vs-new by the acceptance suite on every migration ticket and cut over
2026-10-26 ([docs/cutover-runbook.md](docs/cutover-runbook.md)). Known
weaknesses are deliberately deferred to the post-migration backlog
(issues #2–#7).

## Build & run

```console
$ docker compose up -d --build
$ open http://localhost:8000
```

One web service — the official PHP 8.5.12 Apache image, the framework's
`public/` directory as docroot, dependencies installed by Composer at build
time — plus the MySQL 8.0 service, unchanged from the legacy stack (the
production database carries over as-is). Configuration comes from the
environment file: the committed `env` template carries the docker-compose
development values and the image bakes it to `.env`. No credentials live in
code.

## Test

```console
$ ./scripts/run-acceptance.sh        # bring up, seed, run the acceptance suite
```

The acceptance suite drives the running container over plain HTTP against a
seeded database — see
[docs/testing/acceptance-suite.md](docs/testing/acceptance-suite.md). On the
host that serves production, run it through the isolated verification stack
(`docker-compose.verify.yml`) so its reseeded fixture never touches the
production database — see [docs/cutover-runbook.md](docs/cutover-runbook.md).

## Map

- `docs/adr/` — migration decisions (PHP 8.5.12, parity-first port,
  per-account key retention)
- `docs/cutover-runbook.md` — the maintenance-window cutover, step by step,
  with its rollback
- `docs/research/` — compatibility research feeding the spec
- `docs/testing/` — the acceptance suite and its fixture
- `GLOSSARY.md` — domain terms (User, Vault Account, Media Type, Access Level)
