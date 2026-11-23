# API (`api.php`)

All answers are JSON: `{"ok": true, …}` or `{"ok": false, "error": "message"}`.

| Method | URL | Body | Answer |
|---|---|---|---|
| GET | `api.php?action=shops` | – | `shops[]` (approved shops with `bikes[]`), `sample` (true if sample data is shown) |
| POST | `api.php?action=book` | `{bike_id, name, phone, date: "YYYY-MM-DD", days, helmet?}` | `booking {code, shop, bike, date, days, total, status}` |
| POST | `api.php?action=status` | `{codes: ["GL123456", …]}` (max 20) | `bookings[]` with current status |
| POST | `api.php?action=register` | `{city, name, area, lat, lng, phone, whatsapp?, open, close, type, model, day, hr?}` | `ok` – shop saved as *pending* |

Rules: pickup date today … +90 days, 1–30 days, max `MAX_BOOKINGS_PER_HOUR` bookings per IP per hour, a hidden `website` field must be empty (spam trap). Prices are always read from the database.

## Deploy hook (`migrate.php`)
`POST /migrate.php` with header `X-Migrate-Token: <MIGRATE_TOKEN>` → backup (if there are new migrations) + run migrations. Any other request gets **403**.

## Try it (VS Code REST Client)
```http
GET http://localhost:8000/api.php?action=shops

###
POST http://localhost:8000/api.php?action=book
Content-Type: application/json

{"bike_id": 1, "name": "Test User", "phone": "9876543210", "date": "2026-12-01", "days": 2}
```
