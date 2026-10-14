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
TRUSTED_PROXIES=   # your host's proxy IPs/CIDRs, comma-separated (see below)
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
`db:seed` now refuses to run in production (it would create demo users with password `password`), so do not rely on it there. Create the first admin with (`role` and `status` are not mass-assignable, hence `forceFill`):
```
php artisan tinker
>>> (new App\Models\User)->forceFill(['name'=>'Admin','email'=>'you@school.edu','password'=>'a-long-unique-password','role'=>'admin','status'=>'active'])->save();
```
<!-- BUG B-28 [OPEN]: the first-admin step types the password into tinker, which saves it in the shell's psysh history file (~/.psysh/psysh_history). FIX: add an artisan command (e.g. `app:create-admin`) that asks for the password with a hidden prompt, and document that instead; or tell people to delete the history line. -->
Then create other users from the Users page. Each user can change their password under "Password".

## Trusted proxies (`TRUSTED_PROXIES`)
If the host puts a proxy/load balancer in front of the app (most do), put ITS IPs or CIDRs in `TRUSTED_PROXIES`, e.g. `10.0.0.0/8,172.16.0.0/12`. Ask the host or check its docs. Empty means "trust none", which is right when users reach PHP directly. Otherwise the app sees the proxy's IP for everyone and https redirects may break.
`*` trusts every proxy; use it only if the app can be reached ONLY through the host's proxy, because otherwise a client can forge `X-Forwarded-For` and bypass the login lockout.
Set it as a real host environment variable (not just `.env`): `env()` ignores `.env` after `php artisan config:cache`.

## Protecting the activity log
The log is append-only in the application only. In production give the app's database user just `SELECT, INSERT` on `activity_logs` (and the normal rights on the other tables), and run `php artisan migrate --force` with a separate, more privileged user. Then a bug or a compromised app cannot rewrite history.

## Typical hosting errors
- 500 error, blank page: check `storage/logs/laravel.log`; usually missing `APP_KEY`, wrong DB credentials, or unwritable `storage/`.
- 404 on every route except `/`: web root is not `public/` or rewrite rules missing.
- Redirects to http or mixed-content warnings: the host's proxy must be listed in `TRUSTED_PROXIES` (bootstrap/app.php no longer trusts every proxy) and `APP_URL` must be https.
- `could not find driver`: enable `pdo_mysql` (or `pdo_pgsql`).
