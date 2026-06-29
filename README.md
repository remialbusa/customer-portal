# Customer Portal

> Laravel 11 + Breeze + Livewire 3 + Monday.com integration, deploying to cPanel shared hosting.

A three-in-one portal that lets **customers** open and track support
tickets, **field service engineers (FSEs)** triage and resolve them
with time tracking and internal notes, and **admins / superadmins**
manage invites and approve account-deletion requests — all backed
by Monday.com boards as the system of record.

## Status

| Layer | Status |
|---|---|
| Laravel scaffold | ✅ done |
| Monday.com integration (tickets, TSRs, long_text) | ✅ done |
| Customer ticket creation + duplicate guard | ✅ done |
| TSP dashboard (assigned tickets, chat, internal notes) | ✅ done |
| Time tracker (start/pause/resume/stop) | ✅ done |
| Real-time chat via Pusher | ✅ done |
| Customer invites (Breeze + web UI) | ✅ done |
| Self-service account-deletion (customer + TSP) | ✅ done |
| cPanel deploy + CI/CD | ⏳ pending |

**Tested:** 64/64 E2E assertions pass via in-process PHP
(`portal/scripts/_e2e_workflows.php`).

## Stack

- **Backend:** Laravel 11, PHP 8.5
- **Auth:** Breeze (Blade + Alpine.js, no SPA)
- **UI:** Livewire 3 + Alpine.js + Tailwind CSS + Bootstrap 5 (TSR form)
- **DB:** SQLite (dev) / MySQL (prod)
- **Real-time:** Pusher (ap1)
- **System of record:** Monday.com (GraphQL)
- **Hosting:** cPanel Linux shared (no Node.js in prod)

## Layout

```
customer-portal/
├── .env.example          # environment template (real .env is gitignored)
├── .gitignore            # root ignore rules
├── .gitattributes        # line-ending normalization
├── README.md             # this file
├── customer-portal-build-plan.md   # source of truth for scope/phases
└── portal/               # Laravel 11 application
    ├── app/              # controllers, models, services, Livewire
    ├── bootstrap/        # Laravel bootstrap
    ├── config/           # app, auth, database, services, etc.
    ├── database/         # migrations, seeders, factories
    ├── public/           # web root (index.php, /build assets, /images)
    ├── resources/        # Blade views, CSS, JS
    ├── routes/           # web.php, auth.php, channels.php
    ├── scripts/          # dev/test scripts (E2E, manual smoke)
    ├── storage/          # logs, framework cache, sessions
    ├── tests/            # PHPUnit feature + unit tests
    └── vendor/           # (gitignored)
```

## Quick start (local dev)

```bash
# 1. Clone
git clone https://github.com/remialbusa/customer-portal.git
cd customer-portal

# 2. Env
cp .env.example .env
# Edit .env and set:
#   APP_KEY (run: php artisan key:generate)
#   MONDAY_API_TOKEN
#   PUSHER_APP_KEY / PUSHER_APP_SECRET / PUSHER_APP_ID

# 3. Install PHP deps
cd portal
composer install

# 4. Migrate + seed (optional)
php artisan migrate --seed

# 5. Serve
php artisan serve --host=127.0.0.1 --port=8765 --no-reload
```

Visit `http://127.0.0.1:8765`.

### Test users (after seeding)

| Email | Role | Password |
|---|---|---|
| `customer@example.com` | customer | `Password!123` |
| `remial.busa@mcbtsi.com` | fse | `Password!123` |
| `admin@example.com` | admin | `Password!123` |
| `superadmin@portal.local` | superadmin | `Password!123` |

## Tests

### In-process E2E (64 assertions, ~1s)

```bash
cd portal
php scripts/_e2e_workflows.php
```

Sections: AUTH, CUSTOMER ticket+duplicate guard, TSP time tracker,
internal note+chat, TSR mount/render/submit/sync, ADMIN KPI+invites,
SUPERADMIN deletion inbox, ADMIN 403, self-service deletion,
customer-only registration.

### PHPUnit

```bash
cd portal
php artisan test
```

### TSR form manual smoke (real Monday)

```bash
cd portal
php scripts/test_tsr_form.php
```

## Push to a new repo

```bash
git remote add origin https://github.com/YOUR_USERNAME/customer-portal.git
git push -u origin main
```

## Critical implementation notes

- **`resources/views/layouts/app.blade.php` MUST have `@stack('scripts')` before `</body>`** — otherwise every `@once @push('scripts')` is silently dropped.
- **TSR form uses Bootstrap 5.3 from CDN** (not bundled via Vite) — the CDN survives cPanel-no-Node.
- **Monday `add_file_to_column` MUST be POSTed as multipart to `/v2/file`** — base64 in variables does not work. See [`portal/app/Services/MondayClient.php`](portal/app/Services/MondayClient.php).
- **PHP 8.5 + Eloquent enum casts:** `(string) $enum` throws. Use `->value` or raw column reads.
- **Livewire 3 + Alpine:** `init()` fires twice. Use a guard flag in Alpine factories.
- **`@js` in script blocks:** triggers Blade compile error if the JS comment contains `// @js`. Avoid that pattern.

See the [customer-portal-build-plan.md](customer-portal-build-plan.md) for
the full phased plan and decisions.

## License

Proprietary — internal use only.
