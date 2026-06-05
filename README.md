# The Cotswold Swimming League - Official Website (Season 2026)

This repository contains the source code for the official Cotswold Swimming League website: **thecotswoldleague.co.uk**.

## Project Overview

The Cotswold League is a unique swimming league focused on development, sporting spirit, and providing a platform for less experienced swimmers to compete. This website serves as the central hub for parents, swimmers, coaches, and club representatives, offering real-time information and administrative tools.

## Key Features

* **Real-time Countdown:** Live timer tracking the precise time until the next major league event or final.
* **Interactive Club Map:** A visual directory powered by Leaflet.js, allowing parents and swimmers to locate all participating clubs with integrated directions.
* **Live League Table:** Dynamic standings powered by a MySQL database, automatically updating positions and reveals as scores are processed.
* **Sponsors & Merchandise:** Dedicated section showcasing the main league sponsor (Wyvern Swimwear) and official 2026 merchandise.
* **Spectator Information:** Comprehensive guide for attendees, including admission pricing, parking details, and warm-up times.
* **League History & Archive:** A permanent record of past seasons, including hardcoded results for 2026 and historical data for 2020.

### Admin & Representative Tools

* **Super Admin Dashboard (`league_admin.php`):** A centralized control center for managing the league database, club contacts, venue draws, and official results uploads.
    * Clubs can be retired/reactivated without deleting historical standings, draws, or uploads, and new clubs are created with contact/PIN and active-season results records.
* **Representative Portal (`admin.php`):** A gated gateway for club reps, providing access to the host team checklist and the secure Team Portal.
* **Team Portal (`teamportal.php`):** A sophisticated dashboard for club reps featuring:
    * **Smart Programme Generator:** Automated creation of gala programmes via Google Sheets integration.
    * **Results Matcher:** Intelligent tool for cross-referencing swimmer times with official gala data.
    * **Dynamic Directory Filtering:** Advanced filtering to isolate contacts by standing or specific round match-ups.
* **Audit Logging:** Automated tracking of all logistical changes made by club representatives to ensure transparency and data integrity.

## Technical Documentation

For an in-depth breakdown of the project architecture, database schemas, and complete descriptions of all automated functionality (like the Smart Programmes, Results Matcher, and Animated Showcase), please refer to the comprehensive [Website Guide](WEBSITE_GUIDE.md).

## Technical Details

The project has evolved from a static site to a highly dynamic, DB-driven PHP application.

* **Core Logic:** PHP (v8+) used for backend logic and component-based templating.
* **Database:** MySQL (`cotswold_league`) manages all persistent scoring and logistical data.
* **Frontend:** Styled with **Tailwind CSS** for a modern, responsive user experience.
* **Mapping:** **Leaflet.js** integration for the interactive club directory.
* **Icons:** Powered by **Lucide**.
* **Security:** Multi-tiered authentication system for Super Admins and Club Representatives, hardened session cookies, security headers, restricted uploaded-file access, and audit logging.

## Security & Privacy

The site applies a shared security baseline through `security_headers.php` and `.htaccess`:

* `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`, and HTTPS-only HSTS.
* PHP sessions are started through `cotswold_secure_session_start()` so login cookies use `HttpOnly`, `SameSite=Lax`, strict session mode, and `Secure` when the request is HTTPS.
* Uploaded digital teamsheets are stored under `uploads/teamsheets/`, blocked from direct web access by `.htaccess`, and served only through authenticated PHP download checks.
* Database and admin credentials can be supplied by environment variables: `COTSWOLD_DB_HOST`, `COTSWOLD_DB_USER`, `COTSWOLD_DB_PASS`, `COTSWOLD_DB_NAME`, `COTSWOLD_LEAGUE_PASSWORD`, and `COTSWOLD_SUPER_ADMIN_PASSWORD`.

The privacy policy is maintained in `privacy.php`. It covers public league records, club representative data, swimmer/team information used for digital teamsheets, essential session cookies/browser storage, lawful basis, retention, sharing, security, and individual rights.

## Hosting & Infrastructure

This project is a personal initiative to streamline league operations. It is currently hosted using:

* **XAMPP:** Local Apache server and MySQL database for development and hosting.
* **Cloudflare Tunnels:** Securely exposes the local server to the public domain.
* **GitHub:** Version control and remote backup.

For production, set the environment variables listed above rather than relying on the local XAMPP defaults embedded as development fallbacks.

### Laptop Shared-Folder Development Database

For the laptop/shared-folder copy, keep database credentials in environment variables rather than committing them to `db.php`.

* **Desktop Apache host:** `localhost`
* **Laptop DB host:** `192.168.1.69`
* **DB port:** `3306`
* **DB name:** `cotswold_league`
* **DB user:** `cotswold_user`
* **DB password:** supplied via `COTSWOLD_DB_PASS`

Example local PHP server command:

```sh
COTSWOLD_DB_HOST=192.168.1.69 \
COTSWOLD_DB_USER=cotswold_user \
COTSWOLD_DB_PASS='set-this-in-your-shell' \
COTSWOLD_DB_NAME=cotswold_league \
php -S 127.0.0.1:8000
```

Verification on 2026-05-16:

* TCP access to `192.168.1.69:3306` succeeded.
* PHP loaded the project `db.php` runtime config and connected to `cotswold_league`.
* The existing database reported 15 tables; no schema or seed import was run.
* `http://127.0.0.1:8000/index.php` returned HTTP 200 with no database connection error.

## Pre Commit

### Installing
First install the pre commit package manager: https://pre-commit.com/. e.g `pip install pre-commit` or `brew install pre-commit`.

Install pre commit into the repo via `pre-commit install`, will install at `.git/hooks/pre-commit`.

### Running
This will now run and validate on every commit to the git repo.

### Updating
Update the following file `.pre-commit-config.yaml` found at the base of the repo.

## Maintenance

Managed and maintained by **Lewis Plume** (League Secretary).

*No league funds were used for the creation or maintenance of this project.*

Feedback and historical data submissions are welcome via the contact links on the site.

---
&copy; 2026 The Cotswold Swimming League | Built by Lewis Plume
