# Laravel Taskboard

A small but functional project/task board built with the **Laravel framework** as a canonical application for deployment-target compatibility testing. It ships a real UI, ORM-backed persistence, session authentication, validated CRUD, search/filter, and one database-backed background job.

- **Framework:** Laravel 13 (Laravel Framework 13.x)
- **Runtime:** PHP 8.4 (composer constraint `^8.3`), Composer 2.x, Node.js 22 / npm 10 for the Vite asset build
- **Database:** SQLite via Eloquent (default), with an explicit persistent path
- **Queue / cache / sessions:** database-backed (single-process friendly)
- **License:** MIT — see [LICENSE](LICENSE)

## Feature summary

| Capability | How it is implemented |
|---|---|
| ORM migrations | Eloquent models + migrations for `users`, `projects`, `tasks`, `activity_logs`, plus the framework's `sessions`, `cache`, `jobs` tables |
| Login / session | Email/password login, registration, logout; `database` session driver with CSRF-protected forms |
| Validated CRUD | Projects and tasks with list/create/show/edit/delete, server-side validation, 404 for missing records |
| Search / filter | Task board search by text plus status, priority and project filters; projects filter by status |
| Background job | `App\Jobs\RecordTaskActivity` runs off the `database` queue and writes into the `activity_logs` table |
| Persistence | SQLite writes on every change; data survives restart and redeploy when `DB_DATABASE` points at a persistent path/volume |
| Health | `/health/live` (process liveness) and `/health/ready` (checks the database) |
| Release marker | `/version` endpoint and UI footer expose a non-sensitive `release`/`commit` marker |

## Directory layout

```
app/Http/Controllers/     Web controllers (auth, dashboard, projects, tasks, health)
app/Jobs/                 RecordTaskActivity background job
app/Models/               Project, Task, ActivityLog, User
app/Providers/
config/                   app.php (release markers), database, queue, session, ...
database/migrations/      Schema for users, projects, tasks, activity_logs, sessions...
database/seeders/         Repeatable demo data (demo user, projects, tasks)
resources/views/          Blade templates (auth, dashboard, projects, tasks)
routes/web.php            All HTTP routes
scripts/verify.sh         Clean-checkout verification: install, tests, smoke, persistence
tests/                    PHPUnit feature/unit tests
```

## Requirements

- PHP 8.3 or 8.4 with extensions: `pdo_sqlite`, `mbstring`, `tokenizer`, `xml`, `ctype`, `bcmath`, `openssl`, `curl`, `fileinfo`
- Composer 2.x
- Node.js 22 / npm (for the Vite/Tailwind asset build)
- No external services (no Redis, no separate queue). Everything is database-backed.

## Install and build

```sh
composer install --no-interaction --prefer-dist --optimize-autoloader
cp .env.example .env
php artisan key:generate
npm install --ignore-scripts
npm run build
```

Set up the persistent database (SQLite default):

```sh
mkdir -p /var/lib/laravel-taskboard          # or your chosen persistent path
touch /var/lib/laravel-taskboard/database.sqlite
```

The `.env.example` file already points `DB_DATABASE` at a persistent path. Adjust it for your environment:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/var/lib/laravel-taskboard/database.sqlite
```

Run migrations and (optionally) repeatable seed data:

```sh
php artisan migrate --force
php artisan db:seed --force          # demo user + projects + tasks
```

The seeder creates this demo account:

| Field | Value |
|---|---|
| Email | `demo@example.com` |
| Password | `demo-password` |

## Run in production

The production process serves the built assets through `public/index.php`:

```sh
php artisan serve --host=0.0.0.0 --port=8000
```

Default bind: `0.0.0.0:8000`. Override the port with `--port` or the `PORT` convention of your platform. For multi-process production, run the application behind PHP-FPM or FrankenPHP using the same `public/index.php` entry point; the queue worker and web process share the same database.

Start the database-backed queue worker (background job processor):

```sh
php artisan queue:work --tries=3
```

## Background job

Task create/update/delete dispatch `App\Jobs\RecordTaskActivity`, which is stored in the `jobs` database table and processed by `php artisan queue:work`. Each job writes a non-sensitive activity row to `activity_logs` with the action, message and a small context snapshot.

Smoke check that the worker processes a queued job:

```sh
php artisan queue:work --once --stop-when-empty
```

## Health and readiness

| Endpoint | Purpose | Response |
|---|---|---|
| `/health/live` | Process liveness (server responding) | `200` always when the process is up |
| `/health/ready` | Dependency readiness (database reachable) | `200 ready` or `503 unavailable` |
| `/version` | Non-sensitive release/build info | `200` JSON |
| `/up` | Laravel's default health check | `200` when the app is healthy |

Readiness fails (503) when the database cannot be queried, so a load balancer/orchestrator will not route traffic to a process whose backing store is down.

## Environment variables

See [.env.example](.env.example) for the full commented template. Key variables:

| Variable | Required | Description |
|---|---|---|
| `APP_NAME` | no | Application name shown in the UI |
| `APP_ENV` | yes (prod) | `production` for deployed environments |
| `APP_KEY` | yes | Laravel encryption key (generated by `php artisan key:generate`) |
| `APP_DEBUG` | no | Leave `false` in production |
| `APP_URL` | yes | Public base URL |
| `APP_RELEASE` | no | Build/deploy marker shown in the footer and `/version` |
| `APP_COMMIT` | no | Source revision (commit SHA) shown in `/version` |
| `DB_CONNECTION` | no | `sqlite` (default) |
| `DB_DATABASE` | yes | **Persistent** SQLite file path (must survive restart/redeploy) |
| `SESSION_DRIVER` | no | `database` (default) |
| `CACHE_STORE` | no | `database` (default) |
| `QUEUE_CONNECTION` | no | `database` (default) |

No real credentials are stored in the repository. The `.env.example` uses placeholders only.

## Schema

- `users` — application users (login/session)
- `projects` — board projects (name, description, status `active`/`archived`)
- `tasks` — tasks belonging to a project (title, description, status `todo`/`in_progress`/`done`, priority `low`/`medium`/`high`)
- `activity_logs` — rows written by the background job (project/task FK, action, message, context)
- `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens` — Laravel framework tables

## Persistence

All data is stored in the SQLite file referenced by `DB_DATABASE`. **The database file must live on a persistent volume or external store** and never only inside an ephemeral release directory, or records will be lost on redeploy. The automated persistence check (below) stops and restarts the production server and confirms a written task still exists afterward.

## Automated verification

`scripts/verify.sh` performs a clean-checkout verification from this repository:

1. Verifies PHP and Composer versions.
2. Runs `composer install`, creates `.env`, generates the key, writes the persistent SQLite file, runs migrations and seeds.
3. Builds the Vite/Tailwind assets.
4. Runs the PHPUnit test suite (auth, CRUD, validation, health, job, search/filter).
5. Starts the production server and runs live HTTP smoke checks:
   - liveness and readiness endpoints
   - login flow with CSRF token
   - create / read / update / delete task via HTTP
   - invalid input returns a validation error and no record is created
   - missing record returns 404
   - a mutation without a CSRF token is rejected (419)
6. Runs the database-backed background job end to end (`queue:work`) and verifies the `activity_logs` row.
7. Persistence check: writes a marker task, stops the server, starts it again, and verifies the marker survived before cleaning up.
8. Readiness failure check: starts a server with an unreachable database and verifies `/health/ready` returns 503 while `/health/live` stays 200.

Run it from the repository root:

```sh
bash scripts/verify.sh
```

Requires `curl`, `jq` (optional), and the runtime already installed.

## Testing

```sh
php artisan test
```

The suite uses an in-memory SQLite database (`phpunit.xml`). Tests cover authentication, projects and tasks CRUD/validation/search, health/readiness, and the background job including a real `database` queue dispatch. CSRF is exercised live by `scripts/verify.sh` (a form mutation without a token is rejected with 419), since Laravel's testing framework bypasses CSRF validation while running in unit-test mode.

## Deployment notes for target platforms

- Bind address/port: the example starts on `0.0.0.0:8000`; pass `--host`/`--port` to bind elsewhere.
- Logs: default channel streams to `stderr` so platforms capture stdout/stderr; set `LOG_CHANNEL=single` to use `storage/logs/laravel.log`. No credentials are logged.
- Persistence: mount/persist the directory holding the `DB_DATABASE` SQLite file across redeploys.
- Workers: run at least one `php artisan queue:work` process so background job activity is recorded.
- Release marker: set `APP_RELEASE` (and `APP_COMMIT`) per deploy to distinguish revisions in `/version` and the UI footer.

## License

MIT — see [LICENSE](LICENSE). Laravel is MIT-licensed (Copyright (c) Taylor Otwell) and attribution to its upstream copyright is retained in the license file.