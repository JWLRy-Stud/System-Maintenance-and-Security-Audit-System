# Windows setup (easiest path: Laragon)

Laragon bundles PHP, MySQL, Composer and a terminal, so there is nothing to wire up by hand.

1. Install **Git for Windows** (git-scm.com) and **Laragon Full** (laragon.org).
2. Open Laragon, click **Start All** (Apache/Nginx + MySQL turn green).
3. Click **Terminal** in Laragon, then check the versions: `php -v` must be 8.2 or higher (if not, install a newer PHP via Laragon menu > PHP, or download a newer Laragon). `composer -V` should also work.
4. Clone and install:
   ```
   cd C:\laragon\www
   git clone <your-repo-url> maintenance-audit
   cd maintenance-audit
   composer install
   copy .env.example .env
   php artisan key:generate
   ```
5. Create the database: Laragon > **Database** (opens HeidiSQL) > right-click the session > Create new > Database, name `maintenance_audit`, collation `utf8mb4_unicode_ci`.
   Or in the Laragon terminal: `mysql -u root -e "CREATE DATABASE maintenance_audit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`
6. Open `.env` in VS Code and set (Laragon's MySQL root user normally has an EMPTY password):
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=maintenance_audit
   DB_USERNAME=root
   DB_PASSWORD=
   ```
7. Build tables and run:
   ```
   php artisan migrate --seed
   php artisan serve
   ```
   Open http://127.0.0.1:8000 and log in with admin@example.com / password.
8. Tests: `php artisan test` (needs the `pdo_sqlite` PHP extension; enable it in Laragon > PHP > Extensions if the tests complain).

Next time: start Laragon (Start All), open its Terminal, `cd` to the project, `php artisan serve`.

## Repomix on Windows
Install Node.js LTS (nodejs.org), then in the project folder:
```
npx repomix -c repomix.config.json
```
This creates `system.xml`. Do NOT commit it (it is in .gitignore) and check it for secrets before sharing.

Common problems: "could not find driver" = enable `pdo_mysql` in Laragon > PHP > Extensions. "Access denied" = `.env` username/password don't match your MySQL user. After `git pull`: `composer install` then `php artisan migrate`.
