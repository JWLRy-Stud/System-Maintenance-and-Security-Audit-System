# Deployment checklist (any host with PHP 8.2+ and MySQL/PostgreSQL)

Free tiers change often; verify the host's current terms. Laravel needs PHP + a database + a writable `storage/`, so static hosts (e.g. Vercel) are not suitable.

## Environment variables on the host
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=<output of: php artisan key:generate --show>
APP_URL=https://your-domain
DB_CONNECTION=mysql   # or pgsql
DB_HOST=... DB_PORT=... DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=...
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```
## Deploy steps
```
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
Document root must be `public/`. `storage/` and `bootstrap/cache/` must be writable.

## Accounts
Do NOT run `db:seed` in production (it creates demo users with password `password`). Create the first admin with:
```
php artisan tinker
>>> App\Models\User::create(['name'=>'Admin','email'=>'you@school.edu','password'=>'a-long-unique-password','role'=>'admin','status'=>'active']);
```
Then create other users from the Users page. Each user can change their password under "Password".

## Typical hosting errors
- 500 error, blank page: check `storage/logs/laravel.log`; usually missing `APP_KEY`, wrong DB credentials, or unwritable `storage/`.
- 404 on every route except `/`: web root is not `public/` or rewrite rules missing.
- Redirects to http or mixed-content warnings: proxy headers (already trusted in `bootstrap/app.php`) and `APP_URL` must be https.
- `could not find driver`: enable `pdo_mysql` (or `pdo_pgsql`).
