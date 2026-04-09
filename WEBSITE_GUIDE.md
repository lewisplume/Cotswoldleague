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
Stores static directory information for the 20 participating teams.
* **Key Columns:** `id` (PK), `name`, `logo` (filename reference to the `images/` directory), `pool_name`, `postcode`, `website`.
* **Usage:** Drives the Club Directory page.

### `club_contacts`
Manages the contact details and authentication for club representatives.
* **Key Columns:** `club_id` (FK to clubs), `club_name`, `access_pin` (4-digit code allowing reps access to the portal), contact fields (for up to 3 club officials).
* **Usage:** Integrated tightly with `teamportal.php`, granting reps the ability to update their specific club's logistics by authenticating with their unique `access_pin`.

### `results`
Contains the scoring per club across the different rounds.
* **Key Columns:** `club_id` (FK to clubs), `round_1`, `round_2`, `round_3`, `round_4`, `total`.
* **Usage:** Powers the real-time league table. The admin updates this via `update_scores.php`. 

### `venue_details`
Holds logistical information for the galas, ensuring parents and spectators have consistent info.
* **Key Columns:** `host_club`, `round_number`, `venue_name`, `address`, `warmup_time`, `start_time`, `payment_info`, `parking_info`.
* **Usage:** Displayed heavily in the spectator program and on the public-facing directory. This is dynamically editable by club reps.

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
* **Admin Ecosystem (`admin.php`):** Central dashboard protected by `LEAGUE_PASSWORD` defined in `db.php`. Permits editing of scores, broad venue updates, and access to the unified `audit_log`.
* **Team Portal (`teamportal.php`):** Protected by club-specific PINs. Grants access to:
  * Team sheets and gala paperwork.
  * In-line venue modification forms for their upcoming hosting duties.
  * Advanced integration with Google Sheets for Results and Programmes.

## 4. Notable Implementation Updates

The project has transitioned from static functionality into a highly dynamic and automated ecosystem. Recent milestones include:

### Automating Smart Programme Generation (`smartprogrammenew.php`)
* We replaced the former model of downloading/uploading static files with an automatic Google Sheet API ingestion model.
* Teams can enter their unique Google Sheet ID directly from `teamportal.php`. The backend invokes `smartprogrammenew.php`, reads the Google Sheet array dynamically via a fetch script, parses the data, and renders the 2027 smart programme dynamically.
* Tracks generation instances in `tracking_stats`.

### Integrated Automated Results Matcher (`Results-matcher.php`)
* Embedded fully into the team portal. Allows squads to automatically cross-reference their raw swimmer times with their internal Google Sheets.
* Leverages similar fetch mechanisms from the Smart Programme implementation to bypass manual CSV uploads while simultaneously supporting fallback manual file ingestion.

### Animated Results Showcase (`showcase_finals_presentation.php`)
* A dynamic, high-visual-fidelity presentation screen designed for live gala environments.
* Built exclusively for broadcast, executing a sequential reveal animation for the C, B, and A finals rankings descending from the top spot. 
* Concludes with a grand finale grid utilizing the main league logo as the centerpiece via CSS transitions and Tailwind matrix grids.

### Persistent Auditing & Security Tracking 
* With decentralized access granted to Club Reps for editing venue logistics, an automated `audit_log.php` script was formulated that tracks the user, the old string vs the new string, maintaining 100% visibility over all administrative modifications.

## 5. Deployment / Recreation Instructions

If restoring this project from scratch:
1. **Prepare Server:** Install a fresh build of XAMPP.
2. **Setup DB:** Navigate to `localhost/phpmyadmin`. Create a database named `cotswold_league`.
3. **Import Scheme:** Import the latest `.sql` snapshot from the `db_backups/` folder.
4. **Environment Context:** Ensure `db.php` has `$servername`, `$username`, and `$password` correctly pointing to your local environment (usually `root` and no password out of the box).
5. **Run Setup:** Copy the directory into `htdocs/`. The logic within the system relies on absolute routing relative to the host, meaning navigating to `http://localhost/cotswoldleague` will boot the project instantly.
