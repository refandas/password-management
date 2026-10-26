#!/usr/bin/env bash
#
# Bring the app up, load the repeatable seed fixture, run the acceptance
# suite against the running container. See docs/testing/acceptance-suite.md.
#
set -euo pipefail
cd "$(dirname "$0")/.."

docker compose build web
docker compose up -d

# The db service is intentionally untouched (no healthcheck added). Wait for a
# real query to succeed — `mysqladmin ping` can pass during mysql:8.0's
# first-boot init while the server still restarts (cold volume).
until docker compose exec -T db mysql -uroot -proot -e 'SELECT 1' >/dev/null 2>&1; do
    sleep 1
done

# Load the fixture and assert the DB leg of the path: schema.sql drops and
# recreates every table, so every load starts from the same dataset — and
# the fixture must be exactly this dataset. (The PHPUnit assertions below
# cover the HTTP leg.)
load_seed() {
    docker compose exec -T db mysql -uroot -proot password_manager < tests/seed/schema.sql
    docker compose exec -T db mysql -uroot -proot password_manager < tests/seed/seed.sql

    seeded=$(docker compose exec -T db mysql -uroot -proot -N -B password_manager \
        -e "SELECT (SELECT COUNT(*) FROM user)=3 AND (SELECT COUNT(*) FROM account)=4 AND (SELECT COUNT(*) FROM account_media)=14" \
        | tr -d '[:space:]')
    if [ "$seeded" != "1" ]; then
        echo "seed verification failed: expected 3 users, 4 accounts, 14 media types" >&2
        exit 1
    fi
}

# The cutover proof runs first (ticket #15), against the pristine
# legacy-seeded fixture — every seeded Vault Account still in the legacy CI3
# format — re-encrypting the whole table and proving through the HTTP seam
# that the detail pages keep their seed plaintexts. --no-coverage: the image
# ships no coverage driver and phpunit.dist.xml is failOnWarning.
load_seed
docker compose exec -T -e ACCEPTANCE_BASE_URL=http://localhost web \
    php vendor/bin/phpunit --no-coverage tests/Acceptance/VaultReencryptionTest.php

# The proof leaves the table re-encrypted, so load the fixture once more
# for the parity suite: it starts from the legacy-seeded rows exactly as
# every ticket's run did.
load_seed
docker compose exec -T -e ACCEPTANCE_BASE_URL=http://localhost web \
    php vendor/bin/phpunit --no-coverage tests/Acceptance
