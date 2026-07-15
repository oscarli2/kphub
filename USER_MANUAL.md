# KP-HUB — User Manual

**Version:** Draft
**Last updated:** 2025-11-19

**Contents**
- Introduction
- System Requirements
- Installation
  - Local development
  - Production deployment (Hostinger / cPanel)
- Configuration
  - `db.php`
  - `config/settings.json`
- Database & Migrations
  - Running safe schema fixes (e.g., `fix_columns.php`)
- User Workflows
  - Logging in
  - Creating a post
  - Attaching files and folders
  - Adding links to posts
  - Editing and deleting posts
  - Reactions (like / love / celebrate / insightful)
  - Searching and pagination
  - Notifications
  - Profile update
- File & Folder Management
  - Uploading files to folders
  - Sharing files/folders to newsfeed
  - Google Drive links (if enabled)
- Admin Tasks
  - User management (add / edit / delete)
  - Reports
  - Backups
- Troubleshooting & FAQ
- Appendix
  - Important files
  - Useful commands
  - Contact / Support

---

**Introduction**

KP-HUB is a lightweight PHP/MySQL knowledge-product hub designed to let facility users post updates, share files and folders, and collaborate. It includes a community newsfeed, configurable user roles, file uploads, Google Drive integration (optional), and basic reporting.

**System Requirements**

- PHP 7.4+ (PHP 8 recommended)
- MySQL / MariaDB
- Web server (Apache / Nginx / built-in PHP server for dev)
- Composer only if additional PHP packages are added (not required by default)
- Modern browser (Chrome, Edge, Firefox, Safari)

**Installation**

Local development (quick start):

1. Place project files into your web root (e.g., `c:\wamp64\www\kphub`).
2. Create a MySQL database for KP-HUB.
3. Import the provided SQL dump (if available) or run migration scripts if present.
4. Configure database credentials in `db.php` (see Configuration section).
5. Start local server (for quick testing):

```powershell
# from project root
php -S localhost:8000
# then open http://localhost:8000 in your browser
```

Production deployment (Hostinger / cPanel):

1. Upload project files to your Hostinger account `public_html` (or desired folder).
2. Create a MySQL database and note host, db name, username, and password.
3. Update `db.php` with production credentials.
4. If you have a SQL dump, import it using phpMyAdmin or the Hostinger database tools.
5. Ensure `uploads/` directory is writable by the web server.
6. Remove or restrict access to any diagnostic scripts after setup for security.

**Configuration**

`db.php`:
- This file contains the PDO (or mysqli) connection to the database.
- Edit host, dbname, username, and password to point to your environment.

`config/settings.json`:
- Stores app-level settings (theme/colors or other small flags).
- Edit carefully; invalid JSON will break pages that read it.

Security note: Never commit production credentials to source control. Prefer environment-specific config or server-only files.

**Database & Migrations**

- The app expects a specific schema with tables such as `posts`, `users`, `post_reactions`, `file_downloads`.
- If you see SQL errors like "Unknown column 'links'", your production database schema is older than the code expects.
- A safe approach is to add missing columns with `ALTER TABLE` (non-destructive):

Example (run only if you have a backup and ALTER permissions):

```sql
ALTER TABLE posts ADD COLUMN `title` VARCHAR(255) DEFAULT '';
ALTER TABLE posts ADD COLUMN `links` TEXT DEFAULT NULL;
```

- We have a helper script `fix_columns.php` in the repo (if present) that checks and adds missing columns safely. Always backup the DB first.

**User Workflows**

Logging in:
- Visit `login.html` and enter your credentials. If login fails, check `login.php` for database connectivity and correct credentials.

Creating a Post:
- After logging in, go to the Newsfeed tab.
- Fill the title, write content using the rich text editor, optionally add links and attach files, then click "Share Update".
- Posts support types: text, file, folder (when sharing a folder link), and can include embedded media.

Attaching files & folders:
- Click the paperclip to attach a file when composing a post.
- To share a Google Drive item or a folder, use the folder/file browser and choose "Share to Newsfeed".

Adding links to posts:
- Use the "Add links" toggle in the post creation form; each link requires a label and URL.

Editing/Deleting posts:
- Posts you own display edit and delete buttons (pencil/trash icons).
- Editing reopens the post editor; changes overwrite the post content.

Reactions:
- Click reaction buttons under a post to like, love, celebrate, or mark insightful.
- The UI updates counts and highlights your reaction.

Searching and Pagination:
- Use the search input above the newsfeed to filter posts by title/content.
- Pagination controls appear when there are multiple pages.

Notifications:
- The bell icon shows unread notifications. Click it to view the list and mark items as read.

Profile Update:
- Use `profile.php` and `update_profile.php` to edit your profile details and avatar.

**File & Folder Management**

Uploading to a folder:
- Navigate to folders (View Folders), open a folder, and click "Upload Here".
- Files are sent to Google Drive (if Apps Script integration is configured) or stored locally according to config.

Sharing files/folders to Newsfeed:
- Use the copy/share icons on folder/file cards to share them to the newsfeed with an optional message.

Google Drive integration:
- The system can use a Google Apps Script endpoint for Drive interactions. Check `APPS_SCRIPT_URL` in `index.php` for the configured script.

**Admin Tasks**

User management:
- Admins can add users (`add_user.php`), edit (`edit_user.php`), and delete users (`delete_user.php`).
- Ensure proper privileges are enforced on server side.

Reports:
- The Reports tab may have charting and export functions. Large reports can take time; use the loading modal.

Backups and maintenance:
- Regularly export the MySQL database (via phpMyAdmin or `mysqldump`).
- Backup `uploads/` folder and any external storage links references.

**Troubleshooting & FAQ**

Common issue: Posts not displaying / Missing column error
- Symptom: SQL error "Unknown column 'p.links' in 'SELECT'".
- Cause: Production DB schema is missing columns added in newer code.
- Fix: Backup DB, then run ALTER TABLE statements to add `title` and `links` columns or run `fix_columns.php`.

Common issue: Cannot connect to DB
- Check `db.php` credentials and database host.
- Confirm database user has required permissions.

Common issue: File uploads failing
- Ensure `uploads/` is writable by the web server user.
- If using Google Drive integration, confirm `APPS_SCRIPT_URL` is reachable and valid.

Helpful commands (local dev):

```powershell
# start PHP built-in server
php -S localhost:8000

# export DB using mysqldump (example)
mysqldump -u root -p kphub > kphub_dump.sql
```

**Appendix**

Important files & purpose:
- `index.php` — Main UI, styles and client-side logic.
- `db.php` — Database connection.
- `login.php`, `logout.php`, `login.html` — Authentication.
- `get_posts.php`, `create_post.php` — Posts API endpoints.
- `profile.php`, `update_profile.php` — Profile management.
- `add_user.php`, `edit_user.php`, `delete_user.php` — Admin user management.
- `fix_columns.php` — Utility to add missing columns to `posts` table (if present).
- `uploads/` — Directory for uploaded files.
- `config/settings.json` — App settings.

Contact / Support
- For further assistance, provide the following when contacting support:
  - A clear description of the problem
  - Any relevant SQL errors or PHP errors from server logs
  - Steps to reproduce the issue

---

If you'd like, I can:
- Expand any section into step-by-step screenshots and exact commands.
- Convert this manual into a printable PDF.
- Split the manual into separate `README-ADMIN.md` and `README-USER.md` files.

Tell me which sections you'd like expanded or if you want this committed to the repository as-is.