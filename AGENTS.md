# Laravel Taskboard — agent notes

Laravel 13 application. PHP 8.4, Composer 2, Node 22 / npm (Vite + Tailwind build).

## Project scope

Small project/task board: projects and tasks with ORM migrations, session
authentication (database sessions), validated CRUD, search/filter, one
database-backed background job (`App\Jobs\RecordTaskActivity`), liveness
(`/health/live`), readiness (`/health/ready`) and a `/version` release marker.

## Local development

```sh
cp .env.example .env          # then edit DB_DATABASE to a writable path
php artisan key:generate
npm ci --ignore-scripts && npm run build
php artisan migrate --force --seed
php artisan serve
```

## Test and verification

```sh
php artisan test              # PHPUnit feature/unit suite (in-memory SQLite)
bash scripts/verify.sh        # clean-checkout install + tests + prod smoke + persistence
```

## Conventions

- Keep the app small and dependency-light: SQLite, database sessions/cache/queue by default.
- Never commit `vendor/`, `node_modules/`, `.env`, or the SQLite database.
- `public/build/` IS committed: hosts that deploy from Git without a Node build step (xCloud's default
  Laravel deploy runs only Composer and migrations) need the Vite manifest, or every page returns 500.
  After changing `resources/css`, `resources/js` or the Vite config, run `npm ci --ignore-scripts && npm run build`
  and commit `public/build/`; CI fails when the committed build is stale.
- Keep health/readiness routes outside the session middleware (see `bootstrap/app.php`).
- Preserve MIT attribution for Laravel upstream in `LICENSE`.