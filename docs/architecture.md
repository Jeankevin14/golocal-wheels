# Architecture

```
Browser ──▶ public_html (src/public)          ──▶ app/ (src/app, private)     ──▶ MariaDB
            index.html  (map, booking UI)         config/config.php (.env)        shops, bikes, bookings,
            api.php     (JSON API)                lib/db.php, migrator, backup    admins, shop_comments,
            admin.php   (admin panel)             scripts/backup.php (cron)       schema_migrations
            deploy-hook.php (auto-update, token)
            setup.php   (first admin only)
```

- **Two folders on the server.** `public_html` is reachable from the internet; `app` sits next to it and is not. Passwords (`app/.env`), backups and sessions live in `app`.
- `src/public/_app_path.php` tells the public pages where `app` is (`../app` in production; replaced by the deploy workflow for staging).
- **No build step.** The website is plain HTML/JS; the map uses Leaflet from a CDN and OpenStreetMap tiles.
- **Two database users.** The website uses a limited user (read/write rows only). Migrations and backups use a separate user that may change tables.
- **Sessions** for the admin panel are stored in `app/sessions` (reliable on shared hosting).
- **Security basics:** prepared statements everywhere, CSRF tokens on admin forms, `password_hash` for admin passwords, prices always taken from the database (never from the browser), booking rate limit per IP, spam honeypot, HTTPS forced by `.htaccess`.

## Request flow – booking
1. `index.html` loads shops from `api.php?action=shops` (only `approved` shops with at least one bike).
2. Tourist submits the form → `api.php?action=book` validates, recalculates the price from the database, saves the booking, emails the admin.
3. The browser keeps the booking ID; “My bookings” asks `api.php?action=status`.
4. Admin sees the booking, sends it to the shop on WhatsApp, updates the status.

## Shop approval flow
Imported or requested shops start as **pending** (not on the site). The admin calls, writes a comment, adds bikes, and ticks “Approve” → status `approved` + `verified_at`. The server refuses approval without a logged call and at least one bike.
