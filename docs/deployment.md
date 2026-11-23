# Deployment

**Nobody uploads files by hand.** Git decides what goes live:

| Git action | What happens |
|---|---|
| Pull request opened / updated | **CI**: PHP lint → migrations (twice) → sample data → smoke test |
| Merge into `develop` | **Deploy to staging** – https://staging.golocalwheels.com |
| Merge into `main` | **Deploy to production** – https://golocalwheels.com |

The deploy workflow (`.github/workflows/deploy.yml`):
1. lints the PHP,
2. uploads `src/app/` (+ `database/migrations/`) to the private **app** folder over FTPS – never `.env`, backups or sessions,
3. uploads `src/public/` to the web root,
4. calls `migrate.php` with the secret token → **backup first**, then new migrations,
5. checks that the API and admin page answer.

## Folders on the server
```
/home/golocalwheels/domains/golocalwheels.com/
├── public_html/            ← production web root      (src/public)
│   └── staging/            ← staging web root         (src/public)  – subdomain staging.golocalwheels.com
├── app/                    ← production private folder (src/app) + .env + backups/
└── app-staging/            ← staging private folder    (src/app) + .env + backups/
```

## Turning deploys on
Deploys are **off** until the one-time setup below is done, so merges do not fail. When everything is ready:
repo → **Settings → Secrets and variables → Actions → Variables** → add `DEPLOY_ENABLED` = `true`.

## One-time setup

### 1. FTP account for deploys (DirectAdmin → FTP Management)
Create an FTP user, e.g. `deploy`, with **custom path** `/home/golocalwheels/domains/golocalwheels.com`. Use a long random password. With this path the folders above become `public_html/`, `app/`, `public_html/staging/`, `app-staging/`.

### 2. Staging site and database
1. DirectAdmin → **Subdomain Management** → add `staging` (folder `public_html/staging`).
2. SSL/TLS Certificates → Provision now, so `staging.golocalwheels.com` gets https.
3. Databases → create `golocalwheels_staging` (+ its own users – see [database.md](database.md)).
4. File Manager → create folder `app-staging` next to `public_html`, and in it a file `.env` (copy `.env.example`, `APP_ENV=staging`, staging DB values, a new `MIGRATE_TOKEN`).
5. After the first deploy to staging, open `https://staging.golocalwheels.com/setup.php` to create a staging admin. For test shops on staging, use the admin **Add shop list** button or add shops by hand (never copy production data).

### 3. Production `.env` (moving from the old `config.php`)
1. File Manager → create folder `app` next to `public_html` → new file `.env`:
   ```
   APP_ENV=production
   SITE_URL=https://golocalwheels.com
   DB_HOST=localhost
   DB_NAME=golocalwheels_golocal
   DB_USER=<limited app user, or golocalwheels_golocal for now>
   DB_PASS=<its password>
   DB_MIGRATE_USER=golocalwheels_golocal
   DB_MIGRATE_PASS=<its password>
   MIGRATE_TOKEN=<new 40+ character random string>
   ADMIN_EMAIL=<your email>
   ```
   (copy the database password from the old `public_html/config.php`).
2. After the **first successful production deploy**, delete these old files from `public_html`: `config.php`, `db.php`, `shops-research.json`, `install.php`, `check.php` (if present), and the old `glw_sessions` folder next to it.

### 4. GitHub environments and secrets (repo → Settings → Environments)
Create two environments, **staging** and **production**. Add to each:

| Name | Kind | Production | Staging |
|---|---|---|---|
| `FTP_SERVER` | secret | `golocalwheels.com` | same |
| `FTP_USERNAME` | secret | the deploy FTP user | same |
| `FTP_PASSWORD` | secret | its password | same |
| `FTP_PUBLIC_DIR` | secret | `public_html/` | `public_html/staging/` |
| `FTP_APP_DIR` | secret | `app/` | `app-staging/` |
| `MIGRATE_TOKEN` | secret | same value as in `app/.env` | same value as in `app-staging/.env` |
| `SITE_URL` | variable | `https://golocalwheels.com` | `https://staging.golocalwheels.com` |
| `APP_PATH_FROM_PUBLIC` | variable | *(leave empty → `../app`)* | `../../app-staging` |
| `FTP_PROTOCOL` | variable | `ftps` (use `ftp` only if FTPS is not offered) | same |

Production environment → **Required reviewers**: add yourself, so a production deploy waits for a click.

### 5. Protect `main` (repo → Settings → Branches → Add rule for `main`)
- Require a pull request before merging, **1 approval**
- Require status checks to pass: **CI / test**
- Do not allow bypassing; no force pushes
- Same rule (without required approval if you work alone) for `develop`

> **Plan note:** branch protection and environments for **private** repositories need GitHub Pro (free for students via the GitHub Student Developer Pack). Alternatively make the repository public – no secrets are stored in the code.

## Releasing to production
1. Feature branches → PR into `develop` → CI green → merge → check staging.
2. Update `CHANGELOG.md` (move items from *Unreleased* to a new version).
3. PR `develop` → `main`, approve, merge → production deploy.
4. Tag the release: `git tag -a v1.1.0 -m "v1.1.0" && git push origin v1.1.0` (or GitHub → Releases → *Draft a new release*).

## Hotfix
Branch `hotfix/<name>` from `main` → PR into `main` → after release, merge `main` back into `develop`.

## Rollback
- **Code:** revert the PR on GitHub (*Revert* button) and merge – the deploy puts the previous files back.
- **Database:** every migration has a backup in `app/backups/…-before-migration.sql.gz`. Restore it with phpMyAdmin → Import.
