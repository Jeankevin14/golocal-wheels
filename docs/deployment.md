# Deployment

**Nobody uploads files by hand.** Git decides what goes live:

| Git action | What happens |
|---|---|
| Pull request opened / updated | **CI**: PHP lint → migrations (twice) → sample data → smoke test |
| Merge into `develop` | **Deploy to staging** (https://staging.golocalwheels.com) – once staging is set up |
| Merge into `main` | **Deploy to production** (https://golocalwheels.com) |

## How a deploy works (pull-based, no FTP)
Our hosting allows only one FTP account (the main account), so GitHub never gets an FTP or hosting password. Instead:

1. GitHub Actions (`.github/workflows/deploy.yml`) sends `POST /deploy-hook.php` with the secret **`DEPLOY_TOKEN`** and the commit id.
2. The server checks the token, then asks GitHub which commit is the **current head of its branch** – only that exact commit can be installed.
3. It downloads that commit from GitHub, copies `src/app` → `app/`, `database/migrations` → `app/migrations/`, `src/public` → `public_html/`.
   Never touched: `app/.env`, `app/backups`, `app/sessions`, `app/data` (private shop list), `public_html/_app_path.php`.
4. If there are new migrations: **backup first**, then the migrations.
5. GitHub checks that the API and the admin page answer.

The commit that is live is written to `app/deployed.json`.

> Files deleted from the repository are not deleted on the server. Remove them once by hand in File Manager.

## Folders on the server
```
/home/golocalwheels/domains/golocalwheels.com/
├── public_html/            ← production web root   (src/public)
│   └── staging/            ← staging web root      (later)
├── app/                    ← production private folder (src/app) + .env + backups/ + data/
└── app-staging/            ← staging private folder (later)
```

## One-time switch-over for production (from the old layout)
1. Back up the database: DirectAdmin → Databases → phpMyAdmin → `golocalwheels_golocal` → Export → Go.
2. Upload the switch-over package (`src/app` → `app/`, `src/public` → `public_html/`) once by hand – this is the last manual upload.
3. Create `app/.env` (copy `.env.example`):
   ```
   APP_ENV=production
   SITE_URL=https://golocalwheels.com
   DB_HOST=localhost
   DB_NAME=golocalwheels_golocal
   DB_USER=golocalwheels_golocal
   DB_PASS=<the password from the old public_html/config.php>
   DEPLOY_TOKEN=<new 40+ character random string>
   DEPLOY_BRANCH=main
   ADMIN_EMAIL=<your email>
   ```
4. Move `public_html/shops-research.json` to `app/data/shops-research.json`.
5. Check https://golocalwheels.com and the admin panel, then delete from `public_html`: `config.php`, `db.php`, `shops-research.json`, `install.php` / `check.php` (if present). Delete the old `glw_sessions` folder next to `public_html` (you will log in again once).

## GitHub settings (repo → Settings)
1. **Environments → New environment → `production`**
   - Secret `DEPLOY_TOKEN` = the same value as in `app/.env`
   - Variable `SITE_URL` = `https://golocalwheels.com`
   - *Required reviewers*: yourself (a production deploy then waits for your click)
2. **Secrets and variables → Actions → Variables** → `PRODUCTION_DEPLOY_ENABLED` = `true`
3. **Branches → Add rule** for `main`: require a pull request, 1 approval, status check **CI / test**, no force pushes. Same for `develop`.

## Staging (later)
1. DirectAdmin → Subdomain Management → `staging` (folder `public_html/staging`); SSL → Provision now.
2. Databases → new database `golocalwheels_staging`.
3. File Manager: folder `app-staging` next to `public_html` with its own `.env` (`APP_ENV=staging`, staging DB, `DEPLOY_BRANCH=develop`, a different `DEPLOY_TOKEN`).
4. `public_html/staging/_app_path.php` must contain:
   `<?php return __DIR__ . '/../../app-staging';`
5. Upload `src/public` into `public_html/staging` and `src/app` into `app-staging` once, open `https://staging.golocalwheels.com/setup.php` to create a staging admin.
6. GitHub: environment `staging` with its `DEPLOY_TOKEN` and `SITE_URL`; variable `STAGING_DEPLOY_ENABLED` = `true`.

## Shop research list (not in Git)
The admin “Add shop list” button reads `app/data/shops-research.json`. That file holds business phone numbers of shops that have not agreed to be listed yet, so it is **not stored in this public repository**. Keep your private copy.

## Releasing to production
1. Feature branches → PR into `develop` → CI green → merge → check staging.
2. Update `CHANGELOG.md` (move *Unreleased* items into a new version).
3. PR `develop` → `main`, approve, merge → production deploy.
4. Tag the release: GitHub → Releases → *Draft a new release* → tag `v1.1.0`.

## Hotfix
Branch `hotfix/<name>` from `main` → PR into `main` → afterwards merge `main` back into `develop`.

## Rollback
- **Code:** revert the PR on GitHub (*Revert* button) and merge – the deploy installs the previous code.
- **Database:** every migration has a backup in `app/backups/…-before-migration.sql.gz` – restore with phpMyAdmin → Import.
