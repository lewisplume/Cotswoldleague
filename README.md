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
* **Security:** Multi-tiered authentication system for Super Admins and Club Representatives (PIN-based).

## Hosting & Infrastructure

This project is a personal initiative to streamline league operations. It is currently hosted using:

* **XAMPP:** Local Apache server and MySQL database for development and hosting.
* **Cloudflare Tunnels:** Securely exposes the local server to the public domain.
* **GitHub:** Version control and remote backup.

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
