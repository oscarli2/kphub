# KP-HUB — Admin Guide

**Version:** Draft (2025-11-19)

This guide is for system administrators: installation, configuration, database maintenance, user management, backups, and troubleshooting.

Contents
- Overview
- System Requirements
- Installation & Deployment (step-by-step)
- Configuration (`db.php`, `config/settings.json`)
- Database Schema & Migrations
  - Using `fix_columns.php` safely
- User Management
  - Add / Edit / Delete users
- Backups & Restore
- Security & Permissions
- Logs & Troubleshooting (admin-focused)
- Maintenance Tasks & Checklist

---

## Overview

KP-HUB is a PHP/MySQL app. Admins manage users, control uploads, and keep the system updated and backed up.

## System Requirements

- PHP 7.4+ (8.x recommended)
- MySQL or MariaDB
- Apache/Nginx or any web server that supports PHP
- `uploads/` directory writable by the web server

## Installation & Deployment (step-by-step)

### Local development (quick):

1. Clone or copy project to development machine: `c:\wamp64\www\kphub`.
2. Create a MySQL database (e.g., `kphub_dev`).
3. Import any SQL dump or run migration scripts.
4. Edit `db.php` with database connection for local environment.
5. Start PHP dev server for quick checks:

```powershell
cd C:\wamp64\www\kphub
php -S localhost:8000
```

6. Open `http://localhost:8000`.

### Production deployment (Hostinger / cPanel):

1. Upload files via FTP or the cPanel file manager to `public_html/kphub`.
2. Create a MySQL database and user in cPanel -> MySQL Databases. Note host, dbname, user, password.
3. Import SQL dump via phpMyAdmin or use the migration framework if available.
4. Edit `db.php` with production credentials. Keep this file server-local and avoid storing credentials in repo.
5. Ensure `uploads/` is writable by the web server:

```powershell
# On Windows dev: adjust permissions via Explorer
# On Linux server (SSH):
sudo chown -R www-data:www-data /path/to/public_html/kphub/uploads
sudo chmod -R 755 /path/to/public_html/kphub/uploads
```

6. Remove or secure any diagnostic scripts (e.g., `fix_columns.php`, `analyze_production.php`) from public access after running.

## Configuration

- `db.php` contains the connection. Typical PDO snippet:

```php
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
```

- `config/settings.json` holds optional app settings.

Security: Use environment variables or server-only config files when possible.

## Database Schema & Migrations

- Check `posts` table for expected columns: `post_id`, `user_id`, `content`, `title`, `links`, `post_type`, `file_name`, `created_at`, etc.
- If code expects columns that are missing in production, you'll see errors like "Unknown column 'p.links'".

Safe migration steps (recommended):

1. Backup database.
2. Check current schema:

```sql
DESCRIBE posts;
```

3. Add missing columns non-destructively:

```sql
ALTER TABLE posts ADD COLUMN `title` VARCHAR(255) DEFAULT '';
ALTER TABLE posts ADD COLUMN `links` TEXT DEFAULT NULL;
```

### `fix_columns.php` utility

A helper script `fix_columns.php` (if present) checks for missing columns and runs `ALTER TABLE` safely. Usage:

1. Upload `fix_columns.php` to the server (if not already present).
2. Run it via a browser or CLI (prefer CLI if available):

```powershell
php fix_columns.php
```

3. Confirm output and verify table structure.
4. Remove the script from the server after use.

## User Management

Files: `add_user.php`, `edit_user.php`, `delete_user.php`.

- Adding users: fill required fields and assign role (Admin / Head / User).
- Editing users: update details and roles as needed.
- Deleting users: review related posts/files before deletion. Consider soft-delete by marking inactive if you need to preserve history.

## Backups & Restore

- Database backup (mysqldump):

```powershell
mysqldump -u dbuser -p kphub > kphub_backup_YYYYMMDD.sql
```

- File backup (uploads folder): compress and copy off-server.

```powershell
# On Linux server (example)
cd /path/to/public_html/kphub
tar -czf backups/uploads_YYYYMMDD.tar.gz uploads/
```

- Restore: import SQL via phpMyAdmin or `mysql` CLI and copy files back into `uploads/`.

## Security & Permissions

- Protect `config/` and any diagnostics from public access (use `.htaccess` or server config).
- Ensure `uploads/` cannot execute scripts. On Apache, add an `.htaccess` in `uploads/`:

```
# Deny PHP execution
<FilesMatch "\.(php|php5|phtml)$">
  Deny from all
</FilesMatch>

# Deny direct listing
Options -Indexes
```

- Use HTTPS in production and secure cookies / sessions.

## Logs & Troubleshooting (admin-focused)

Common issues and fixes:

1. "Unknown column 'p.links'": schema mismatch — backup DB and run `ALTER TABLE` statements or `fix_columns.php`.
2. Database connection errors: confirm credentials in `db.php` and that MySQL server is reachable.
3. File upload failures: ensure `uploads/` writable and check webserver/PHP upload limits (`upload_max_filesize`, `post_max_size`).
4. Slow reports: increase PHP memory/time limits or run reports offline.

Useful commands:

```powershell
# Start PHP dev server
php -S localhost:8000

# Export DB
mysqldump -u root -p kphub > kphub_dump.sql

# Check posts table schema via MySQL CLI
mysql -u root -p -e "DESCRIBE kphub.posts;"
```

## Maintenance Tasks & Checklist

- Daily: monitor error logs, check disk usage for `uploads/`.
- Weekly: backup database and uploads.
- Monthly: review user accounts, rotate admin passwords, and test restore procedures.
- After code deploy: run schema checks, remove diagnostics, and test core flows (login, create post, upload file).

---

If you'd like, I can:
- create the `screenshots/` folder with placeholder images,
- add `.htaccess` snippet as a file in `uploads/`,
- create a small `maintenance.md` checklist and automated backup script example.

Which admin expansion should I produce next?