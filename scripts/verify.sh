#!/usr/bin/env bash
#
# Laravel Taskboard — clean-checkout verification.
#
# Exercises the application end to end as a production process:
#   1. verify runtime requirements
#   2. install Composer + npm dependencies and build assets
#   3. create a persistent SQLite database, run migrations and seed
#   4. run the PHPUnit test suite
#   5. start the production server and run live HTTP smoke checks
#      (health, login, CRUD, validation errors, 404, CSRF rejection)
#   6. process a database-backed background job and verify the result
#   7. verify data survives a server restart (persistence)
#   8. verify readiness fails when the database is unavailable
#
# Usage: bash scripts/verify.sh
# Requires: php, composer, node/npm, curl, sqlite3 (optional), jq (optional)
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${VERIFY_PORT:-8088}"
BASE="http://127.0.0.1:${PORT}"
DATA_DIR="${VERIFY_DATA_DIR:-${ROOT}/var/verify}"
DB_FILE="${DATA_DIR}/database.sqlite"
COOKIE_JAR="$(mktemp)"
PIDS=()
FAILURES=0

log()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32mPASS\033[0m %s\n' "$*"; }
fail() { printf '\033[1;31mFAIL\033[0m %s\n' "$*"; FAILURES=$((FAILURES + 1)); }

check() { # check <description> <expected> <actual>
    if [[ "$2" == "$3" ]]; then ok "$1"; else fail "$1 (expected $2, got $3)"; fi
}

cleanup() {
    for pid in "${PIDS[@]:-}"; do kill "$pid" 2>/dev/null; done
    rm -f "$COOKIE_JAR"
}
trap cleanup EXIT

cd "$ROOT"

###############################################################################
log "1. Runtime requirements"
###############################################################################
php -v | head -1
composer --version
node -v
npm -v

PHP_MAJOR="$(php -r 'echo PHP_MAJOR_VERSION;')"
PHP_MINOR="$(php -r 'echo PHP_MINOR_VERSION;')"
PHP_PATCH="$(php -r 'echo PHP_RELEASE_VERSION;')"
PHP_VER_NUM="$((PHP_MAJOR * 10000 + PHP_MINOR * 100 + PHP_PATCH))"
if [[ "$PHP_VER_NUM" -ge 80401 ]]; then
    ok "PHP >= 8.4.1"
else
    fail "PHP >= 8.4.1 (got $PHP_MAJOR.$PHP_MINOR.$PHP_PATCH)"
fi

composer check-platform-reqs >/dev/null 2>&1 && ok "composer platform requirements" || fail "composer platform requirements"

###############################################################################
log "2. Dependencies and asset build"
###############################################################################
composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader
check "composer install" "0" "$?"

if [[ ! -f .env ]]; then
    cp .env.example .env
fi

# Point at the persistent verification database and set a release marker.
APP_KEY_CURRENT="$(grep -E '^APP_KEY=' .env || true)"
sed -i \
    -e "s|^APP_ENV=.*|APP_ENV=production|" \
    -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
    -e "s|^DB_DATABASE=.*|DB_DATABASE=${DB_FILE}|" \
    -e "s|^APP_RELEASE=.*|APP_RELEASE=verify-$(git rev-parse --short HEAD 2>/dev/null || echo dev)|" \
    -e "s|^APP_COMMIT=.*|APP_COMMIT=$(git rev-parse HEAD 2>/dev/null || echo unknown)|" \
    .env
if [[ "$APP_KEY_CURRENT" == "APP_KEY=" || "$APP_KEY_CURRENT" == "" ]]; then
    php artisan key:generate --force --ansi
fi

mkdir -p "$DATA_DIR"
touch "$DB_FILE"

npm ci --ignore-scripts && npm run build
check "npm ci" "0" "$?"

###############################################################################
log "3. Schema and repeatable seed data"
###############################################################################
php artisan migrate --force
check "migrate" "0" "$?"
php artisan db:seed --force
check "db:seed (repeatable)" "0" "$?"
php artisan db:seed --force
check "db:seed (second run idempotent)" "0" "$?"

php artisan migrate:status | grep -q "Ran"
ok "migrate:status shows ran migrations"

###############################################################################
log "4. PHPUnit test suite"
###############################################################################
php artisan test
check "php artisan test" "0" "$?"

###############################################################################
log "5. Start production server and run HTTP smoke checks"
###############################################################################
php artisan serve --host=127.0.0.1 --port="${PORT}" > "${DATA_DIR}/server.log" 2>&1 &
PIDS+=("$!")
for _ in $(seq 1 30); do
    if curl -sf "${BASE}/health/live" >/dev/null 2>&1; then break; fi
    sleep 1
done

code_curl() { curl -s -o /dev/null -w '%{http_code}' "$@"; }

check "liveness (db up)" "200" "$(code_curl "$BASE/health/live")"
check "readiness (db up)" "200" "$(code_curl "$BASE/health/ready")"
check "/version" "200" "$(code_curl "$BASE/version")"
check "unauth dashboard redirects" "302" "$(code_curl "$BASE/dashboard")"

# --- login (CSRF flow) ---
LOGIN_HTML="$(curl -s -c "$COOKIE_JAR" "$BASE/login")"
CSRF="$(echo "$LOGIN_HTML" | grep -oP 'name="_token" value="\K[^"]+' | head -1)"
[[ -n "$CSRF" ]] && ok "login form exposes CSRF token" || fail "login form CSRF token"
AUTH_CODE="$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" -o /dev/null -w '%{http_code}' \
    -d "_token=${CSRF}&email=demo@example.com&password=demo-password" "$BASE/login")"
check "login" "302" "$AUTH_CODE"
check "dashboard after login" "200" "$(code_curl -b "$COOKIE_JAR" "$BASE/dashboard")"

# --- task CRUD via HTTP ---
CREATE_HTML="$(curl -s -b "$COOKIE_JAR" "$BASE/tasks/create")"
CSRF="$(echo "$CREATE_HTML" | grep -oP 'name="_token" value="\K[^"]+' | head -1)"
PROJECT_ID="$(echo "$CREATE_HTML" | grep -oP '<option value="\K[0-9]+' | head -1)"

MARKER="verify-$(date +%s)"
CREATE_CODE="$(curl -s -b "$COOKIE_JAR" -o /dev/null -w '%{http_code}' \
    -d "_token=${CSRF}&project_id=${PROJECT_ID}&title=${MARKER}&description=smoke&status=todo&priority=high" \
    "$BASE/tasks")"
check "create task" "302" "$CREATE_CODE"

TASK_PAGE="$(curl -s -b "$COOKIE_JAR" "$BASE/tasks?q=${MARKER}")"
echo "$TASK_PAGE" | grep -q "$MARKER" && ok "created task visible" || fail "created task visible"
TASK_ID="$(echo "$TASK_PAGE" | grep -oP '/tasks/\K[0-9]+' | head -1)"

EDIT_HTML="$(curl -s -b "$COOKIE_JAR" "$BASE/tasks/${TASK_ID}/edit")"
CSRF="$(echo "$EDIT_HTML" | grep -oP 'name="_token" value="\K[^"]+' | head -1)"
UPDATE_CODE="$(curl -s -b "$COOKIE_JAR" -o /dev/null -w '%{http_code}' -X PUT \
    -d "_token=${CSRF}&project_id=${PROJECT_ID}&title=${MARKER}-updated&description=edited&status=done&priority=low" \
    "$BASE/tasks/${TASK_ID}")"
check "update task" "302" "$UPDATE_CODE"
curl -s -b "$COOKIE_JAR" "$BASE/tasks?q=${MARKER}" | grep -q "${MARKER}-updated" \
    && ok "updated task persisted" || fail "updated task persisted"

# --- invalid input (validation) ---
INVALID="$(curl -s -b "$COOKIE_JAR" -o /dev/null -w '%{http_code}' \
    -d "_token=${CSRF}&project_id=${PROJECT_ID}&title=&status=bogus&priority=none" \
    "$BASE/tasks")"
check "invalid input rejected" "302" "$INVALID"
curl -s -b "$COOKIE_JAR" "$BASE/tasks?q=bogus" | grep -q "No tasks found" \
    && ok "invalid input did not create a record" || fail "invalid input created a record"

# --- not found ---
check "missing task 404" "404" "$(code_curl -b "$COOKIE_JAR" "$BASE/tasks/999999")"

# --- CSRF rejection (no token) ---
CSRF_CODE="$(curl -s -b "$COOKIE_JAR" -o /dev/null -w '%{http_code}' \
    -d "project_id=${PROJECT_ID}&title=no-csrf&status=todo&priority=medium" "$BASE/tasks")"
check "mutation without CSRF rejected (419)" "419" "$CSRF_CODE"

###############################################################################
log "6. Database-backed background job end to end"
###############################################################################
JOBS_BEFORE="$(php artisan tinker --execute="echo \Illuminate\Support\Facades\DB::table('jobs')->count();")"
CREATE_HTML="$(curl -s -b "$COOKIE_JAR" "$BASE/tasks/create")"
CSRF="$(echo "$CREATE_HTML" | grep -oP 'name="_token" value="\K[^"]+' | head -1)"
curl -s -b "$COOKIE_JAR" -o /dev/null \
    -d "_token=${CSRF}&project_id=${PROJECT_ID}&title=job-driver-${MARKER}&status=in_progress&priority=medium" \
    "$BASE/tasks"
JOBS_AFTER="$(php artisan tinker --execute="echo \Illuminate\Support\Facades\DB::table('jobs')->count();")"
check "job queued to database queue" "$((JOBS_BEFORE + 1))" "$JOBS_AFTER"

php artisan queue:work --once --stop-when-empty --tries=1
check "queue:work processes queued job" "0" "$?"

ACTIVITY_COUNT="$(php artisan tinker --execute="echo \App\Models\ActivityLog::where('action', 'task.created')->count();")"
[[ "$ACTIVITY_COUNT" -gt 0 ]] && ok "activity_log row written by background job" \
    || fail "activity_log row written by background job"

###############################################################################
log "7. Persistence across server restart"
###############################################################################
BEFORE="$(php artisan tinker --execute="echo \App\Models\Task::where('title', 'like', 'verify-%')->count();")"
kill "${PIDS[0]}" 2>/dev/null; wait "${PIDS[0]}" 2>/dev/null
sleep 1

php artisan serve --host=127.0.0.1 --port="${PORT}" >> "${DATA_DIR}/server.log" 2>&1 &
PIDS[0]=$!
for _ in $(seq 1 30); do
    if curl -sf "${BASE}/health/live" >/dev/null 2>&1; then break; fi
    sleep 1
done

AFTER="$(php artisan tinker --execute="echo \App\Models\Task::where('title', 'like', 'verify-%')->count();")"
check "tasks survive restart" "$BEFORE" "$AFTER"
LOGIN_HTML="$(curl -s -c /tmp/verify-cookie2.txt "$BASE/login")"
CSRF="$(echo "$LOGIN_HTML" | grep -oP 'name="_token" value="\K[^"]+' | head -1)"
curl -s -b /tmp/verify-cookie2.txt -c /tmp/verify-cookie2.txt -o /dev/null \
    -d "_token=${CSRF}&email=demo@example.com&password=demo-password" "$BASE/login"
curl -s -b /tmp/verify-cookie2.txt "$BASE/tasks?q=${MARKER}" | grep -q "${MARKER}-updated" \
    && ok "persisted task reachable via HTTP after restart" || fail "persisted task via HTTP after restart"
rm -f /tmp/verify-cookie2.txt

###############################################################################
log "8. Readiness fails when the database is unavailable"
###############################################################################
DOWN_DB="${DATA_DIR}/missing/db.sqlite"
mkdir -p "${DATA_DIR}/missing" 2>/dev/null || true
# A path that cannot be opened proves readiness reacts to dependency loss.
DOWN_DB="/proc/laravel-taskboard-nope/db.sqlite"

DB_DATABASE="$DOWN_DB" php artisan serve --host=127.0.0.1 --port="$((PORT + 1))" >> "${DATA_DIR}/server-down.log" 2>&1 &
PIDS+=("$!")
DOWN_BASE="http://127.0.0.1:$((PORT + 1))"
for _ in $(seq 1 30); do
    BODY="$(curl -s "$DOWN_BASE/health/live" 2>/dev/null || true)"
    [[ "$BODY" == *"ok"* ]] && break
    sleep 1
done

check "liveness still 200 with DB down" "200" "$(code_curl "$DOWN_BASE/health/live")"
check "readiness 503 with DB down" "503" "$(code_curl "$DOWN_BASE/health/ready")"
READY_BODY="$(curl -s "$DOWN_BASE/health/ready")"
echo "$READY_BODY" | grep -q "unavailable" && ok "readiness reports unavailable" \
    || fail "readiness reports unavailable"

###############################################################################
log "Verification summary"
###############################################################################
printf '\nDatabases: %s\n' "$DB_FILE"
if [[ "$FAILURES" -eq 0 ]]; then
    printf '\033[1;32mALL CHECKS PASSED\033[0m\n'
else
    printf '\033[1;31m%s CHECK(S) FAILED\033[0m\n' "$FAILURES"
    exit 1
fi