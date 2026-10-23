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

docker compose exec -T db mysql -uroot -proot password_manager < tests/seed/schema.sql
docker compose exec -T db mysql -uroot -proot password_manager < tests/seed/seed.sql

# Assert the DB leg of the path: the fixture must be exactly this dataset.
# (The PHPUnit assertion below covers the HTTP leg; the default route does not
# open the database yet — per-endpoint DB assertions begin with ticket 2.)
seeded=$(docker compose exec -T db mysql -uroot -proot -N -B password_manager \
    -e "SELECT (SELECT COUNT(*) FROM user)=2 AND (SELECT COUNT(*) FROM account)=4 AND (SELECT COUNT(*) FROM account_media)=14" \
    | tr -d '[:space:]')
if [ "$seeded" != "1" ]; then
    echo "seed verification failed: expected 2 users, 4 accounts, 14 media types" >&2
    exit 1
fi

# The suite drives the container over plain HTTP from inside it. --no-coverage:
# the image ships no coverage driver and phpunit.dist.xml is failOnWarning.
docker compose exec -T -e ACCEPTANCE_BASE_URL=http://localhost web \
    php vendor/bin/phpunit --no-coverage tests/Acceptance
