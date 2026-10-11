# Setup guide (Ubuntu)

## 0. Put the project in your Git repo
1. Unzip the file. You get a folder `system-maintenance-security-audit/`.
2. Copy the CONTENTS of that folder (not the folder itself) into the root of your cloned repo, so `composer.json`, `artisan`, `app/` etc. sit next to your existing `README.md` (it's fine to overwrite that README).
   ```bash
   unzip system-maintenance-security-audit.zip
   cp -a system-maintenance-security-audit/. /path/to/your-repo/
   cd /path/to/your-repo
   ```
   (`cp -a .../.` also copies hidden files such as `.env.example` and `.gitignore`.)
3. Commit: `git add . && git commit -m "Add Laravel project"`. `.gitignore` already excludes `.env` and `vendor/`.

## 1. Dependencies
| Needed | Why |
|---|---|
| PHP 8.2+ with extensions: cli, mbstring, xml, curl, zip, mysql, sqlite3 | Laravel 12 (sqlite3 is only for tests) |
| Composer | installs Laravel and PHP packages into `vendor/` |
| MySQL server | the database |
| Git | version control |
Node.js is NOT needed (Bootstrap loads from a CDN, so you need internet when viewing pages).

Install on Ubuntu:
```bash
sudo apt update
sudo apt install -y php-cli php-mbstring php-xml php-curl php-zip php-mysql php-sqlite3 unzip git composer mysql-server
php -v        # must be 8.2 or higher
```
Ubuntu 24.04 ships PHP 8.3, so you're done. Ubuntu 22.04 ships PHP 8.1, which is too old; run this first, then install PHP 8.3 packages:
```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php && sudo apt update
sudo apt install -y php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-mysql php8.3-sqlite3
```

## 2. MySQL: install, start, create the database
```bash
sudo systemctl enable --now mysql
sudo mysql
```
Inside the MySQL prompt (choose your own password):
```sql
CREATE DATABASE maintenance_audit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'audit_user'@'localhost' IDENTIFIED BY 'ChangeMe_123';
GRANT ALL PRIVILEGES ON maintenance_audit.* TO 'audit_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Configure and run the project
```bash
composer install
cp .env.example .env
php artisan key:generate
```
Edit `.env` and set:
```
DB_DATABASE=maintenance_audit
DB_USERNAME=audit_user
DB_PASSWORD=ChangeMe_123
```
Then:
```bash
php artisan migrate --seed     # creates tables + demo users/assets
php artisan serve              # open http://127.0.0.1:8000
php artisan test               # run the automated tests
```
Login: admin@example.com / password (also technician@, auditor@, staff@example.com).

## 4. Where is the database? (not in the repo)
MySQL runs as a separate service and stores its data on your machine (`/var/lib/mysql`). The repo contains only the blueprint: `database/migrations/` (table structure) and `database/seeders/` (demo data). Every teammate installs MySQL, creates their own empty database, and runs `php artisan migrate --seed` to rebuild it. Never commit `.env` (it holds your DB password).

## 5. Common problems
- `could not find driver` → `sudo apt install php-mysql` (tests: `php-sqlite3`).
- `Access denied for user` → `.env` username/password don't match step 2; run `php artisan config:clear`.
- `Your PHP version ... does not satisfy` → PHP is older than 8.2 (see step 1).
- `No application encryption key` → run `php artisan key:generate`.
- After pulling teammates' changes: `composer install && php artisan migrate`.
