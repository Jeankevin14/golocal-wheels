# Changelog
All notable changes to GoLocal Wheels. Format: [Keep a Changelog](https://keepachangelog.com), versions: [SemVer](https://semver.org).

## [Unreleased]
### Changed
- Deploys are now pull-based: GitHub signals `deploy-hook.php` with a secret token and the server downloads and installs the exact commit itself (backup + migrations included). No FTP account or hosting password is stored on GitHub.

### Added
- Project moved to GitHub with the team project standard: folder layout, docs, VS Code settings, docker-compose for local development.
- Database migrations (`database/migrations`) run automatically on deploy, with a backup before each change.
- Daily database backup script (keeps 14 days).
- CI: PHP lint, migrations, sample data and smoke test on every pull request.
- Deploy by Git: `develop` → staging, `main` → production.
- Settings read from `.env` instead of `config.php`; private code moved outside the web root.
- `setup.php` replaces `install.php` (creates the first admin only).

## [1.0.0] – 2026-11-21 (before Git)
### Added
- Live map website with filters, near me, directions, ride routes.
- Saved bookings with booking ID and status.
- Admin panel: bookings, shops, bikes, WhatsApp shortcuts.
- 30 researched shops (Pondicherry + Chennai) and call-to-approve flow.
- HTTPS with Let’s Encrypt.
