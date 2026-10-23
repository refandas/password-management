# password-management

A self-hosted password manager: Users store Vault Accounts (credentials for
other services) encrypted at rest, organized by Media Type.

**In migration** (spec #1): the CI3/PHP 7.3 app is being ported to
CodeIgniter 4 on PHP 8.5.12 with 1:1 behavior parity. The legacy app remains
runnable as the comparison oracle from the `pre-migration` branch — see
[docs/testing/legacy-oracle.md](docs/testing/legacy-oracle.md).

## Run

```console
$ docker compose up -d --build
$ open http://localhost:8000
```

Configuration comes from the environment file: the committed `env` template
carries the docker-compose development values and the image bakes it to
`.env`. No credentials live in code.

## Test

```console
$ ./scripts/run-acceptance.sh        # bring up, seed, run the acceptance suite
```

The acceptance suite drives the running container over plain HTTP against a
seeded database — see [docs/testing/acceptance-suite.md](docs/testing/acceptance-suite.md).

## Map

- `docs/adr/` — migration decisions (PHP 8.5.12, parity-first port,
  per-account key retention)
- `docs/research/` — compatibility research feeding the spec
- `docs/testing/` — acceptance suite and legacy oracle
- `GLOSSARY.md` — domain terms (User, Vault Account, Media Type, Access Level)
