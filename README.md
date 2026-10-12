# System-Maintenance-and-Security-Audit-System

Laravel 12 · MySQL · Blade · Bootstrap 5 · PHPUnit. A modular monolith for tracking IT assets, maintenance work and security audit findings.

> Scope is **proposed by the student group** (the professor gave only the system name). See `docs/REQUIREMENTS.md`.

## Setup (full Ubuntu guide with MySQL install: `docs/SETUP.md`)
```bash
composer install
cp .env.example .env
php artisan key:generate
# create an empty MySQL database named "maintenance_audit" (or edit DB_* in .env)
php artisan migrate --seed
php artisan serve        # http://127.0.0.1:8000
```

## Demo accounts (seeded, password `password`: change before any real use)
| Role | Email |
|---|---|
| Administrator | admin@example.com |
| IT Technician | technician@example.com |
| Security Auditor | auditor@example.com |
| Staff | staff@example.com |

## Tests
```bash
php artisan test        # uses in-memory SQLite (needs the pdo_sqlite extension)
```

## Layout
- `app/Http/Controllers` thin HTTP layer · `app/Http/Requests` validation · `app/Http/Middleware/RoleMiddleware.php` server-side role gate
- `app/Services` workflow rules (`MaintenanceService`) and audit trail (`ActivityLogService`)
- `app/Models` Eloquent models; status transitions live in `MaintenanceRequest::TRANSITIONS` and `AuditFinding::TRANSITIONS`
- `routes/web.php` is the single place to see who can reach what
- `docs/` requirements, architecture, security notes

## Repomix
Docs: `docs/PROGRESS.md` (status), `docs/HANDOFF_CHECKLIST.md` (switching Claude accounts), `docs/DEPLOYMENT.md`.

**Windows users:** see `docs/WINDOWS_SETUP.md`. **Using Claude chat:** see `docs/AI_CONTEXT.md`.

`npx repomix -c repomix.config.json` produces a filtered `system.xml` snapshot (no `.env`, vendor or logs). Review it for secrets before sharing.
