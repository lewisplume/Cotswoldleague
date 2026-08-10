# The Cotswold Swimming League - Website Guide and Architecture

This is a maintained orientation guide, not a guarantee of completeness or security. For the current risk baseline and deployment procedures, use [CODEBASE_AUDIT_2026-08-10.md](CODEBASE_AUDIT_2026-08-10.md) and [OPERATIONS_RUNBOOK.md](OPERATIONS_RUNBOOK.md). Historical Word documents and handoff notes may describe superseded behaviour.

## 1. Project Overview & Technologies

### 1.1 Summary
The website is a centralized platform for the Cotswold Swimming League, delivering real-time standings, club information, spectator forms, and programmatic generation of match programs. The system features both public-facing screens and protected admin/club rep portals.

### 1.2 Technology Stack
* **Frontend / UI:** 
  * HTML5 paired with a vendored, version-recorded **Tailwind CSS browser build**.
  * **Vanilla JavaScript** for asynchronous interactions, countdown timers, and micro-animations.
  * Vendored Lucide icons, SheetJS, Alpine, Leaflet and HTML-to-PDF browser libraries under `assets/vendor/`.
* **Backend:** 
  * **PHP (8+)** handles business logic, server-side data fetching, and HTML templating (e.g., using `nav.php` for consistent navigation).
* **Database:** 
  * **MySQL/MariaDB** stores all persistent data relating to scores, club access credentials, venue logistics, and usage metrics.
* **Hosting & Infrastructure:** 
  * Running locally on **XAMPP** (Apache + MySQL/MariaDB).
  * Exposed publicly via **Cloudflare Tunnels**. 

## 2. In-Depth Database Architecture

The data layer runs on a relational MySQL database named `cotswold_league`. Connection logic is managed centrally within `db.php`, mapping credentials from the environment when available.

Supported environment variables:
* `COTSWOLD_DB_HOST`
* `COTSWOLD_DB_USER`
* `COTSWOLD_DB_PASS`
* `COTSWOLD_DB_NAME`
* `COTSWOLD_LEAGUE_PASSWORD`
* `COTSWOLD_SUPER_ADMIN_PASSWORD`

If these are not set, the site falls back to local XAMPP development defaults. Production hosting should provide the environment variables so live credentials are not hardcoded.

The application now uses more than the six original tables. The principal groups are club/contact/venue/results, gala events/scoresheets/teams/results, digital swimmers/teamsheets/entries/audit, global settings, audit logs and telemetry. Confirm the deployed schema before any migration; `db.php` still contains transitional runtime DDL that is tracked as technical debt.

### `clubs`
Stores static directory information for the participating teams.
* **Key Columns:** `id` (PK), `name`, `logo`, `pool_name`, `postcode`, `website`, `latitude`, `longitude`.
* **Usage:** Drives the Club Directory and the Interactive Map. Coordinates are used to plot clubs via Leaflet.js.

### `club_contacts`
Manages the contact details and authentication for club representatives.
* **Key Columns:** `club_id` (FK to clubs), `club_name`, `access_pin` (4-digit code allowing reps access to the portal), contact fields (for up to 3 club officials).
* **Usage:** Grants reps access to the `teamportal.php` to manage their specific club's logistics.

### `results`
Contains the scoring per club across the different rounds.
* **Key Columns:** `club_id` (FK to clubs), `round_1`, `round_2`, `round_3`, `round_4`, `total`.
* **Usage:** Powers the real-time league table.

### `venue_details`
Holds logistical information for the galas, ensuring parents and spectators have consistent info.
* **Key Columns:** `club_id` (FK to clubs), `round_number`, `round_date`, `venue_name`, `address`, `warmup_time`, `start_time`, `payment_info`, `parking_info`, `team_1_id` to `team_4_id` (FKs to clubs), `results_file`, `teamsheet_link`.
* **Usage:** Manages the gala schedule, competing teams (draw), and hosts. Results and teamsheets are linked here for easy access.

### `audit_log`
A security and traceability layer introduced to observe changes on the platform.
* **Key Columns:** `club_name`, `action`, `change_details`, `timestamp`.
* **Usage:** Whenever a club rep alters `venue_details` (e.g., parking changes or warmup times) from the team portal, the delta is stamped into this log for transparency.

### `tracking_stats`
Provides simple app telemetry metrics.
* **Key Columns:** `action_name`, `count`.
* **Usage:** Logs event hits, tracking metrics like how many times the `programme_generated` or `report_generated` actions were carried out historically.

## 3. Core Functionalities & Routing

* **Public Information Pages:** `index.php`, `table.php`, `spectators.php`, `history.php`, `clubs.php`.
* **Interactive Club Map (`clubs.php`):** A Leaflet.js implementation featuring custom map markers and interactive popups for all league clubs, powered by coordinates stored in the `clubs` table.
* **Super Admin Dashboard (`league_admin.php`):** A restricted dashboard for league administrators. Features include:
  * Full CRUD management for clubs, contacts, and venues.
  * Uploading official gala results (PDF/Excel) and mapping Google Sheet teamsheet links.
  * Real-time telemetry monitoring and statistics reset.
* **Team Portal (`teamportal.php`):** Protected by club-specific PINs and the main entry point for club representatives from public `Team Login`. Features include:
  * Tabbed sections: Overview (operations), Documents (governance & printable resources), Host Checklist, League Directory, and Account (contacts/PIN).
  * Integrated Smart Programme & Results Matcher tools.
  * Real-time venue logistics editing (with automated audit logging).
  * **Dynamic Directory Filtering:** A multi-tiered filtering system allowing reps to isolate club contacts based on current standings (Finals A/B/C) or specific match-ups for any given round.
  * One-click "Email Selected" and "Copy List" functionality for club communications.
  * Host checklist progress stored per club and season in browser `localStorage`.

## 4. Notable Implementation Updates

The project has transitioned from static functionality into a highly dynamic and automated ecosystem. Recent milestones include:

### Interactive Club Maps (`clubs.php`)
* Integrated **Leaflet.js** to provide a visual directory of all clubs.
* Custom map markers use the league logo and provide instant access to pool addresses and club websites.
* Admin controls in `league_admin.php` allow for manual coordinate overrides and geocoding management.

### Super Admin Dashboard Overhaul (`league_admin.php`)
* Centralized all database management into a single, high-security dashboard.
* Replaced manual database edits with user-friendly forms for managing club logos, contact details, and venue draws.
* Added support for uploading official results files and managing collated teamsheet links for both Rounds and Finals.

### Automating Smart Programme Generation (`smartprogrammenew.php`)
* Replaced static files with an automatic Google Sheet API ingestion model.
* Teams access their unique programme via a `sheet_id` parameter. The tool now supports **Programme Type Selection**, allowing users to switch between Rounds and A/B/C Finals, which automatically adjusts event distances and time limits.

### Collated Teamsheet Integration
* Integrated external Google Sheets links directly into the Team Portal.
* Super Admins can map specific links to venues, which then appear as "View Teamsheets" buttons for all competing clubs in that gala.

### Animated Results Showcase (`showcase_finals_presentation.php`)
* A dynamic, high-visual-fidelity presentation screen designed for live gala environments.
* Built exclusively for broadcast, executing a sequential reveal animation for the C, B, and A finals rankings descending from the top spot. 
* Concludes with a grand finale grid utilizing the main league logo as the centerpiece via CSS transitions and Tailwind matrix grids.

### Static Archive Preservation (`history.php`)
* Implemented a static "capture" of the 2026 season results to ensure the historical record remains permanent regardless of future database resets.
* Added historical data for the 2020 season to the archives.

### Persistent Auditing & Security Tracking 
* Audit records cover venue changes and protected request paths. They do not provide named-user accountability while shared club/admin credentials remain, and coverage must not be described as 100%.

### Security Headers, Sessions, and Upload Controls
* `security_headers.php` is included by `db.php` and by session entry points before `session_start()`. It sends the shared security baseline: CSP, frame denial, MIME sniffing protection, referrer policy, permissions policy, cross-origin opener policy, HSTS for HTTPS requests, and removal of `X-Powered-By`.
* Authenticated pages use `cotswold_secure_session_start()` so PHP session cookies are `HttpOnly`, `SameSite=Lax`, strict-mode, and `Secure` when accessed over HTTPS.
* `security_headers.php` is the single authoritative header layer. `.htaccess` denies source-only paths and executable uploads but does not duplicate CSP.
* Uploaded digital teamsheet documents live in `uploads/teamsheets/`, which is blocked from direct web access. `digital_teamsheet_file.php` validates the viewer's session, checks gala/club sharing rules, resolves the file path under the expected upload directory, and serves it as a private attachment.
* `fetch_sheet.php` verifies TLS certificates when downloading Google Sheet exports.
* `track_action.php` accepts same-origin POST usage, validates the action name format, and updates counters via prepared statements. It is telemetry, not an authentication or audit control.

### Privacy Policy
* `privacy.php` is the canonical public privacy notice. It covers:
  * The Cotswold League as controller for website and portal data.
  * Public league records such as club details, fixtures, tables, and results.
  * Portal data such as club representative contact details, access records, digital teamsheets, swimmer names, age groups, dates of birth where needed, PBs, availability, uploaded files, notes, and audit history.
  * Legitimate interests as the usual lawful basis for league administration, competition management, records, support, and security.
  * Essential PHP session cookies/browser storage, with no analytics, advertising, marketing, or tracking cookies currently declared.
  * Sharing, retention, security measures, individual rights, and the ICO complaint route.

## 5. Deployment / Recreation Instructions

If restoring this project from scratch:
1. **Prepare Server:** Install a fresh build of XAMPP.
2. **Setup DB:** Create a database named `cotswold_league` using a least-privilege runtime account.
3. **Restore Data:** Restore only from an approved encrypted backup held outside the repository and web document root. There is intentionally no `db_backups/` source folder.
4. **Environment Context:** For production, set `COTSWOLD_DB_HOST`, `COTSWOLD_DB_USER`, `COTSWOLD_DB_PASS`, `COTSWOLD_DB_NAME`, `COTSWOLD_LEAGUE_PASSWORD`, and `COTSWOLD_SUPER_ADMIN_PASSWORD`. For local XAMPP development, the fallback values in `db.php` usually work out of the box.
5. **Run Setup:** Copy the directory into `htdocs/`. The logic within the system relies on absolute routing relative to the host, meaning navigating to `http://localhost/cotswoldleague` will boot the project instantly.
