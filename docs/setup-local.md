# Run GoLocal Wheels on your computer

## Option A – Docker (recommended)
Needs [Docker Desktop](https://www.docker.com/products/docker-desktop/) and VS Code.

1. VS Code → **Git: Clone** → `https://github.com/Jeankevin14/golocal-wheels.git`
2. Install the recommended extensions when VS Code asks (or Extensions → *Show Recommended*).
3. In the VS Code terminal:
   ```bash
   docker compose up -d
   docker compose exec web php /repo/scripts/migrate.php
   docker compose exec web php /repo/scripts/seed.php
   docker compose exec web php /repo/scripts/create-admin.php admin your-password-here
   ```
4. Open http://localhost:8000 (site) and http://localhost:8000/admin.php (admin).
5. Run the checks CI runs: `docker compose exec web bash -c "cd /repo && BASE_URL=http://localhost bash tests/smoke.sh"`

Database tools (VS Code MySQL client): host `localhost`, port `3307`, user `glw_local`, password `glw_local_pass`, database `glw_local`.

## Option B – without Docker
Needs PHP 8.x with `pdo_mysql` and a local MariaDB/MySQL (e.g. XAMPP).
1. Create a database and user, copy `.env.example` to `src/app/.env`, fill in the values (`APP_ENV=local`).
2. `php scripts/migrate.php && php scripts/seed.php && php scripts/create-admin.php admin your-password-here`
3. `php -S localhost:8000 -t src/public`
4. `BASE_URL=http://localhost:8000 bash tests/smoke.sh`

## Everyday workflow
```bash
git checkout develop && git pull
git checkout -b feature/short-name
# … change code, test locally …
git add -A && git commit -m "feat: short description"
git push -u origin feature/short-name
# open a pull request into develop on GitHub
```
Database change? Add `database/migrations/00X_what_changes.sql` (never edit an old one) and run `php scripts/migrate.php`.
