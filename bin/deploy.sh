#!/usr/bin/env bash
#
# bin/deploy.sh
#
# Deploy Kasko to the Lightsail production host. Automates the
# manual steps we used to run one at a time: fast-forward-pull
# master, rebuild the app image, roll containers forward without
# touching persistent volumes, run migrations, warm cache, and
# smoke-test the site over HTTPS.
#
# Refuses to run when the working tree is dirty, the branch isn't
# master, or the pull can't fast-forward. Never removes named
# volumes, never rewrites git history, never modifies .env.prod.

set -Eeuo pipefail

# ------------------------------------------------------------------
# Constants
# ------------------------------------------------------------------
readonly BRANCH="master"
readonly SITE_URL="https://kaskoconstruction.com"
readonly DB_HEALTH_TIMEOUT=90         # seconds
readonly APP_READY_TIMEOUT=60         # seconds
readonly HEALTH_CHECK_ATTEMPTS=5
readonly HEALTH_CHECK_SLEEP=3         # seconds between attempts
readonly SEP="=================================================================="

# ------------------------------------------------------------------
# Helpers
# ------------------------------------------------------------------
stage() {
    echo
    echo "${SEP}"
    echo "==>  $*"
    echo "${SEP}"
}

log() {
    printf '    %s\n' "$*"
}

die() {
    echo
    echo "!!! ${*}" >&2
    exit 1
}

trap 'rc=$?; echo; echo "!!! DEPLOY FAILED (exit ${rc})" >&2; exit ${rc}' ERR
trap 'echo; echo "!!! deploy interrupted"; exit 130' INT TERM

# ------------------------------------------------------------------
# 0. Locate the repo root and verify this is Kasko
# ------------------------------------------------------------------
script_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
repo_root=$(cd "${script_dir}/.." && pwd)
cd "${repo_root}"

stage "0. Verify Kasko repository context"

[ -d .git ]             || die "not a git repository: ${repo_root}"
[ -f docker-compose.yml ] || die "docker-compose.yml not found at ${repo_root}"
[ -f composer.json ]    || die "composer.json not found at ${repo_root}"

# The Kasko\ PSR-4 prefix in composer.json is the fingerprint for
# "this is the Kasko app". Written as "Kasko\\": in the JSON file
# (two literal backslashes); -F treats the pattern as a fixed string
# so no regex escaping is needed.
grep -qF '"Kasko\\":' composer.json \
    || die "composer.json is missing the Kasko\\\\ autoload prefix; this doesn't look like the Kasko repo"

[ -f .env.prod ] \
    || die ".env.prod not found at ${repo_root}; production credentials must live there (gitignored). Refusing to continue without it."

log "repo:  ${repo_root}"

# ------------------------------------------------------------------
# 1. Refuse to deploy with uncommitted changes
# ------------------------------------------------------------------
stage "1. Refuse to deploy with uncommitted changes"

if ! git diff --quiet \
   || ! git diff --cached --quiet \
   || [ -n "$(git ls-files --others --exclude-standard)" ]; then
    echo
    git status --short
    die "working tree has uncommitted changes; commit or stash before deploying"
fi

log "working tree clean"

# ------------------------------------------------------------------
# 2. Confirm current branch is master
# ------------------------------------------------------------------
stage "2. Confirm current branch is '${BRANCH}'"

current_branch=$(git rev-parse --abbrev-ref HEAD)
[ "${current_branch}" = "${BRANCH}" ] \
    || die "expected branch '${BRANCH}', got '${current_branch}'"

log "on ${BRANCH}"

# ------------------------------------------------------------------
# 3. Display currently deployed commit
# ------------------------------------------------------------------
stage "3. Currently deployed commit"

readonly CURRENT_SHA=$(git rev-parse HEAD)
git log -1 --pretty='format:    %h  %s%n    author: %an <%ae>%n    date:   %ai%n' HEAD

# ------------------------------------------------------------------
# 4. Fetch origin
# ------------------------------------------------------------------
stage "4. git fetch origin"

git fetch origin

# ------------------------------------------------------------------
# 5. Fast-forward pull
# ------------------------------------------------------------------
stage "5. git pull --ff-only origin ${BRANCH}"

# --ff-only refuses to create a merge commit. If origin has diverged
# from local master (i.e., someone did a force-push or a non-ff push
# to master), this fails cleanly and the deploy stops. No auto-reset,
# no auto-merge, no history rewriting.
git pull --ff-only origin "${BRANCH}"

readonly NEW_SHA=$(git rev-parse HEAD)
if [ "${NEW_SHA}" = "${CURRENT_SHA}" ]; then
    log "already at ${NEW_SHA:0:7} — no new commits (continuing to reconcile containers with current tree)"
fi

# ------------------------------------------------------------------
# 6. Display new commit being deployed
# ------------------------------------------------------------------
stage "6. New commit being deployed"

git log -1 --pretty='format:    %h  %s%n    author: %an <%ae>%n    date:   %ai%n' HEAD

# ------------------------------------------------------------------
# 7. Build the application image
# ------------------------------------------------------------------
stage "7. docker compose build app"

docker compose build app

# ------------------------------------------------------------------
# 8. Bring the stack up
# ------------------------------------------------------------------
stage "8. Bring stack up (recreate containers as needed; volumes preserved)"

# `docker compose up -d` will:
#   * start any stopped services (db, app, nginx)
#   * recreate containers whose config or image digest changed
#     (app will be recreated because the image was just rebuilt)
#   * leave already-current containers alone
#
# It will NOT delete named volumes. Deleting volumes requires the
# --volumes / -v flag, which is intentionally not used ANYWHERE in
# this script. The mysql_data, app_public, and app_var volumes all
# persist across deploys.
docker compose up -d

# ------------------------------------------------------------------
# 9. Wait for MySQL to become healthy
# ------------------------------------------------------------------
stage "9. Wait for MySQL healthcheck (timeout: ${DB_HEALTH_TIMEOUT}s)"

db_container=$(docker compose ps -q db)
[ -n "${db_container}" ] || die "db container not found"

elapsed=0
while true; do
    health=$(docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}no-healthcheck{{end}}' "${db_container}")
    if [ "${health}" = "healthy" ]; then
        log "db is healthy after ${elapsed}s"
        break
    fi
    if [ "${health}" = "no-healthcheck" ]; then
        die "db container has no healthcheck configured (expected mysqladmin ping in docker-compose.yml)"
    fi
    if [ "${elapsed}" -ge "${DB_HEALTH_TIMEOUT}" ]; then
        die "db did not become healthy within ${DB_HEALTH_TIMEOUT}s (last status: ${health})"
    fi
    log "db status: ${health} — waiting..."
    sleep 3
    elapsed=$((elapsed + 3))
done

# ------------------------------------------------------------------
# 9b. Wait for the app entrypoint to finish (php-fpm master running)
# ------------------------------------------------------------------
stage "9b. Wait for app to reach php-fpm (timeout: ${APP_READY_TIMEOUT}s)"

# docker/entrypoint.sh runs cache:warmup as www-data BEFORE it execs
# php-fpm. If we start running migrations or a second cache:warmup
# while the entrypoint is still writing var/cache/prod, the two writes
# race on the same directory. We wait until the container's main
# process is php-fpm (i.e. the entrypoint has completed) before we
# run any Symfony console commands.
elapsed=0
while true; do
    if docker compose exec -T app pgrep -f 'php-fpm: master' >/dev/null 2>&1; then
        log "php-fpm master is running after ${elapsed}s"
        break
    fi
    if [ "${elapsed}" -ge "${APP_READY_TIMEOUT}" ]; then
        die "app container did not reach php-fpm within ${APP_READY_TIMEOUT}s"
    fi
    log "app: entrypoint still running — waiting..."
    sleep 3
    elapsed=$((elapsed + 3))
done

# ------------------------------------------------------------------
# 10. Run Doctrine migrations non-interactively
# ------------------------------------------------------------------
stage "10. Run Doctrine migrations"

# --no-interaction is the only Doctrine flag used here. Nothing
# destructive: no doctrine:schema:drop, no schema:update, no
# database:drop, no database:create. If the migration set is empty
# this is a no-op; if a migration fails, `set -e` stops the deploy.
docker compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction

# ------------------------------------------------------------------
# 11. Clear and warm the Symfony production cache
# ------------------------------------------------------------------
stage "11. Clear and warm Symfony production cache"

# Post-migration cache rebuild: the entrypoint already warmed a
# cache once, but that was against the pre-migration schema. Any
# compiled container references to entity metadata should be dropped
# and re-warmed against the migrated DB.
docker compose exec -T app php bin/console cache:clear  --env=prod --no-debug
docker compose exec -T app php bin/console cache:warmup --env=prod --no-debug

# ------------------------------------------------------------------
# 12. Container status
# ------------------------------------------------------------------
stage "12. Container status"

docker compose ps

# ------------------------------------------------------------------
# 13. HTTPS health check
# ------------------------------------------------------------------
stage "13. HTTPS health check against ${SITE_URL}"

# Retries: nginx and app are up, but TLS handshake / first-request
# opcache warmup can take a beat. Up to HEALTH_CHECK_ATTEMPTS tries
# with HEALTH_CHECK_SLEEP seconds between them.
health_ok=0
for attempt in $(seq 1 "${HEALTH_CHECK_ATTEMPTS}"); do
    if code=$(curl -sSL -o /dev/null -w '%{http_code}' --max-time 10 "${SITE_URL}"); then
        if [ "${code}" = "200" ]; then
            log "OK — ${SITE_URL} returned ${code} (attempt ${attempt}/${HEALTH_CHECK_ATTEMPTS})"
            health_ok=1
            break
        fi
        log "attempt ${attempt}/${HEALTH_CHECK_ATTEMPTS}: HTTP ${code}"
    else
        log "attempt ${attempt}/${HEALTH_CHECK_ATTEMPTS}: curl error"
    fi
    if [ "${attempt}" -lt "${HEALTH_CHECK_ATTEMPTS}" ]; then
        sleep "${HEALTH_CHECK_SLEEP}"
    fi
done

if [ "${health_ok}" -ne 1 ]; then
    die "health check failed after ${HEALTH_CHECK_ATTEMPTS} attempts against ${SITE_URL}"
fi

# ------------------------------------------------------------------
# Success
# ------------------------------------------------------------------
stage "SUCCESS"

deployed_sha=$(git rev-parse HEAD)
deployed_short=$(git rev-parse --short HEAD)
deployed_subject=$(git log -1 --pretty='%s' HEAD)

cat <<EOM

    Deployed Kasko to production.

    from:    ${CURRENT_SHA:0:7}
    to:      ${deployed_short}  ${deployed_subject}
    commit:  ${deployed_sha}
    site:    ${SITE_URL}

EOM
