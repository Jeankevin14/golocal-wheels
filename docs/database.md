# Database

MariaDB 10.4 (same version locally, in CI and on the hosting).

## Tables
| Table | What it holds | Key fields |
|---|---|---|
| `shops` | Rental shops | city, name, area, lat, lng, phone, whatsapp, open_time, close_time, status (`pending`/`approved`/`hidden`), verified_at, is_sample, notes |
| `bikes` | Bikes of a shop | shop_id, type, model, price_day, price_hour, deposit, qty |
| `bookings` | Booking requests | code, shop_id, bike_id, customer_name, customer_phone, pickup_date, days, total, status |
| `shop_comments` | Call notes per shop | shop_id, note, approved, created_at |
| `admins` | Admin logins | username, pass_hash |
| `schema_migrations` | Which migration files have run | version, applied_at |

## Rules
1. **Every schema change is a new numbered file** in `database/migrations/` (e.g. `003_add_owner_login.sql`), reviewed in a pull request. Never edit a file that has already run.
2. Migrations run automatically on deploy (`migrate.php`), after a backup. Locally: `php scripts/migrate.php`.
3. **Separate database per environment:** local (docker), staging (`…_staging`), production (`…_golocal`). Testing never uses production.
4. **Seeds** (`database/seeds/`) are sample data for local and staging only; `scripts/seed.php` refuses to run when `APP_ENV=production`.
5. Connection settings only through `.env` / environment variables.
6. Production database is not reachable from the internet (DirectAdmin: “localhost” only).

## Two database users (production and staging)
| User | Rights | Used by |
|---|---|---|
| `…_app` | SELECT, INSERT, UPDATE, DELETE | the website (`DB_USER`) |
| `…_golocal` (existing) | all on this database | migrations and backups (`DB_MIGRATE_USER`) |

DirectAdmin → Databases → *your database* → **Manage users** → *Create user* → untick everything except SELECT, INSERT, UPDATE, DELETE.

## Backups
- **Daily:** DirectAdmin → Cron Jobs → every day 02:30:
  `php /home/golocalwheels/domains/golocalwheels.com/app/scripts/backup.php`
  Keeps 14 days in `app/backups/` (outside the website).
- **Before every migration:** `migrate.php` takes a backup automatically.
- **Off-server copy:** once a week, download the newest file from File Manager → `app/backups/` (or use DirectAdmin’s own account backup).
- **Monthly restore test:** create an empty test database, import the newest backup (DirectAdmin → phpMyAdmin → Import, or `zcat file.sql.gz | mysql test_db`), check that `shops` and `bookings` have rows. Note the date in the checklist below.

| Restore tested on | By | Result |
|---|---|---|
| | | |
