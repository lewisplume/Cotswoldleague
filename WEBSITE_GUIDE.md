# The Cotswold Swimming League - Website Guide and Architecture

This document serves as an in-depth technical guide to the Cotswold Swimming League website. It has been created to provide a complete understanding of how the system works, the technologies involved, the database schemas, and recent advanced updates implemented. This guide should be used if the site needs to be understood, maintained, or recreated in the future.

## 1. Project Overview & Technologies

### 1.1 Summary
The website is a centralized platform for the Cotswold Swimming League, delivering real-time standings, club information, spectator forms, and programmatic generation of match programs. The system features both public-facing screens and protected admin/club rep portals.

### 1.2 Technology Stack
* **Frontend / UI:** 
  * HTML5 paired with **Tailwind CSS** (via CDN) for rapid, responsive UI development.
  * **Vanilla JavaScript** for asynchronous interactions, countdown timers, and micro-animations.
  * **Lucide Icons** (via CDN).
* **Backend:** 
  * **PHP (8+)** handles business logic, server-side data fetching, and HTML templating (e.g., using `nav.php` for consistent navigation).
* **Database:** 
  * **MySQL/MariaDB** stores all persistent data relating to scores, club access credentials, venue logistics, and usage metrics.
* **Hosting & Infrastructure:** 
  * Running locally on **XAMPP** (Apache + MySQL/MariaDB).
  * Exposed publicly via **Cloudflare Tunnels**. 

## 2. In-Depth Database Architecture

The data layer runs on a relational MySQL database named `cotswold_league`. Connection logic is managed centrally within `db.php`, mapping credentials from the environment.

There are **6 core tables** that drive the platform:

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
* **Representative Portal (`admin.php`):** A gated dashboard for club reps providing quick access to the Team Portal, the Host Team Checklist, and essential printable documents.
* **Team Portal (`teamportal.php`):** Protected by club-specific PINs. Features include:
  * Integrated Smart Programme & Results Matcher tools.
  * Real-time venue logistics editing (with automated audit logging).
  * **Dynamic Directory Filtering:** A multi-tiered filtering system allowing reps to isolate club contacts based on current standings (Finals A/B/C) or specific match-ups for any given round.
  * One-click "Email Selected" and "Copy List" functionality for club communications.

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
* With decentralized access granted to Club Reps for editing venue logistics, an automated `audit_log.php` script was formulated that tracks the user, the old string vs the new string, maintaining 100% visibility over all administrative modifications.

## 5. Deployment / Recreation Instructions

If restoring this project from scratch:
1. **Prepare Server:** Install a fresh build of XAMPP.
2. **Setup DB:** Navigate to `localhost/phpmyadmin`. Create a database named `cotswold_league`.
3. **Import Scheme:** Import the latest `.sql` snapshot from the `db_backups/` folder.
4. **Environment Context:** Ensure `db.php` has `$servername`, `$username`, and `$password` correctly pointing to your local environment (usually `root` and no password out of the box).
5. **Run Setup:** Copy the directory into `htdocs/`. The logic within the system relies on absolute routing relative to the host, meaning navigating to `http://localhost/cotswoldleague` will boot the project instantly.
