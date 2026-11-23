# GoLocal Wheels 🛵

**Find, compare and book bikes from local rental shops – Pondicherry first, then Chennai, Bengaluru and Goa.**

Live site: https://golocalwheels.com · Admin: https://golocalwheels.com/admin.php

Big rental apps leave out the small local shops that most tourists actually rent from. GoLocal Wheels puts those shops on one live map with clear prices, and a shop appears on the site only after the team has called it and confirmed it.

## Features
- Live map (Leaflet + OpenStreetMap) with price pins, filters, “near me”, directions and ride routes
- Bookings saved in MariaDB with a booking ID and live status (“My bookings”)
- Shop owners can ask to be listed
- Admin panel: bookings, WhatsApp shop/customer in one tap, shops, bikes and prices
- **Call-to-approve:** every shop has call notes; it goes live only after a logged call + approval
- Email alerts for new bookings and shop requests

## How it is built
| Part | Technology |
|---|---|
| Website | HTML, CSS, JavaScript, Leaflet |
| Server code | PHP 8 (no framework) |
| Database | MariaDB 10.4 |
| Hosting | DirectAdmin shared hosting (LiteSpeed), Let’s Encrypt SSL |
| Deploy | GitHub Actions → FTPS, migrations run automatically |

## Folder structure
```
golocal-wheels/
├── src/
│   ├── public/          # web root → public_html on the server (index.html, api.php, admin.php …)
│   └── app/             # private folder → next to public_html, never reachable from the web
│       ├── config/      # settings (read from .env)
│       ├── lib/         # database, migrations, backups helpers
│       ├── scripts/     # server-side jobs (daily backup)
│       └── data/        # researched shop list for the admin import
├── database/
│   ├── migrations/      # every schema change, numbered – run by the deploy pipeline
│   └── seeds/           # sample data for local and staging only
├── scripts/             # command-line tools: migrate, seed, create-admin, lint
├── tests/               # smoke test used by CI
├── docs/                # requirements, architecture, API, database, deployment, setup
├── .github/workflows/   # CI (every PR) and deploy (develop → staging, main → production)
├── .vscode/             # recommended extensions and shared editor settings
├── docker-compose.yml   # local website + MariaDB
└── .env.example         # names of all settings – real values never go in Git
```
> The standard `components/` and `types/` folders are not used: the frontend is a single page without a build step and PHP has no separate type files.

## Run it on your computer
Full guide: [docs/setup-local.md](docs/setup-local.md)
```bash
git clone https://github.com/Jeankevin14/golocal-wheels.git
cd golocal-wheels
docker compose up -d
docker compose exec web php /repo/scripts/migrate.php
docker compose exec web php /repo/scripts/seed.php
docker compose exec web php /repo/scripts/create-admin.php admin your-password-here
# open http://localhost:8000  and  http://localhost:8000/admin.php
```

## Where to change things
| I want to change… | File |
|---|---|
| Home page, map, booking form | `src/public/index.html` |
| Cities, ride routes, travel tips | `src/public/data.js` (the `CITIES` part) |
| Booking / shop API | `src/public/api.php` |
| Admin panel | `src/public/admin.php` |
| Settings (database, email) | `.env` – see `.env.example` |
| Database tables | add a **new** file in `database/migrations/` |

## How we work
- Branches: `main` (production) ← `develop` (staging) ← `feature/*`, `fix/*`, `hotfix/*`
- Commits: `feat: …`, `fix: …`, `chore: …`, `docs: …`, `refactor: …`, `test: …`
- Every change goes through a pull request; CI must pass; `main` is protected
- Nobody uploads files by hand – merging deploys. See [docs/deployment.md](docs/deployment.md)

## Documents
[Requirements](docs/requirements.md) · [Architecture](docs/architecture.md) · [API](docs/api.md) · [Database](docs/database.md) · [Deployment](docs/deployment.md) · [Local setup](docs/setup-local.md) · [Changelog](CHANGELOG.md)

Original documents: [SRS v2 (Word)](docs/files/GoLocalWheels_SRS_v2.docx) · [Customer challenges study (PDF)](docs/files/RideOn_Challenges_and_Solutions.pdf)
