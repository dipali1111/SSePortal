# Sumruddha Sala E-Portal — Notification & Alerts Dashboard (PHP)

A PHP-backed rebuild of the notification dashboard. All data — notifications,
schools, projects, deadlines, activity log — lives in a real database and is
served through a small JSON API. The frontend (same design as before) fetches
from that API instead of using a hardcoded JS array.

## Quick start (local testing, zero config)

Requires PHP 8+ with the `pdo_sqlite` extension (bundled with most PHP
installs).

```bash
php -S localhost:8000
```

Open `http://localhost:8000/index.php`. On the very first request the app
automatically creates `data/app.sqlite`, applies the schema, and seeds it
with the starting Kolhapur district data — no manual setup step.

## Deploying to a real host (e.g. shared hosting / cPanel)

1. Upload the whole folder to your web root.
2. Make sure `data/` is writable by the web server (`chmod 775 data`).
3. Visit the site — the SQLite database initializes itself on first load,
   same as local testing. Nothing else to configure.

## Switching to MySQL for production

SQLite is fine for a single small server, but for a real district-wide
deployment you'll likely want MySQL:

1. Create a database and import the schema:
   ```bash
   mysql -u root -p -e "CREATE DATABASE sumruddha_sala CHARACTER SET utf8mb4"
   mysql -u root -p sumruddha_sala < database/schema.mysql.sql
   ```
2. Edit `includes/config.php`:
   ```php
   define('DB_DRIVER', 'mysql');
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'sumruddha_sala');
   define('DB_USER', 'your_user');
   define('DB_PASS', 'your_password');
   ```
3. Load your own school/project/notification data through the `projects`,
   `schools`, `notifications`, `deadlines`, and `activity_log` tables (see
   `database/seed.php` for the shape of the data and Kolhapur starting set —
   port it to MySQL INSERT statements, or write a small import script from
   your existing records).

## Project structure

```
index.php                    Main page shell (HTML only — data loads via JS)
assets/css/style.css         All styling (unchanged design/theme)
assets/js/app.js             Fetches everything from api/*.php and renders it
includes/config.php          DB driver + credentials
includes/db.php              PDO connection, auto-creates & seeds SQLite on first run
includes/helpers.php         Time formatting, JSON response helpers
database/schema.sqlite.sql   SQLite schema (used automatically)
database/schema.mysql.sql    MySQL schema (for production deployment)
database/seed.php            Starting data: Kolhapur district, 12 talukas, schools, projects, notifications
api/notifications.php        GET  — list notifications (supports ?filter= and ?search=)
api/notification_detail.php  GET  — full detail for the side drawer (?id=)
api/mark_read.php            POST — mark one notification or all as read
api/action.php               POST — Approve / Request Update / Resolve / Download actions from the drawer
api/stats.php                GET  — summary cards, priority chips, analytics rings, weekly chart
api/deadlines.php            GET  — upcoming deadlines list
api/activity.php             GET  — recent activity feed
api/export.php               GET  — CSV export of the current filtered view
data/                        SQLite database file lives here (auto-created)
```

## Notes

- The "Advanced Filters" panel (District, Taluka, Funding Source, etc.) is
  present in the UI but only `filter` (type) and `search` are currently wired
  to the API — extending the other dropdowns to real query parameters is a
  straightforward addition to `api/notifications.php` and `assets/js/app.js`
  if/when you need it.
- There's no authentication layer yet. The SRS defines CEO / Sachiv / HM
  roles with different permissions — this build doesn't implement login or
  role-based access, since that's a separate feature from the notification
  data layer.
