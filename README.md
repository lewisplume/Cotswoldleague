# The Cotswold Swimming League - Official Website (Season 2026)

This repository contains the source code for the official Cotswold Swimming League website: **thecotswoldleague.co.uk**.

## Project Overview

The Cotswold League is a unique swimming league focused on development, sporting spirit, and providing a platform for less experienced swimmers to compete. This website serves as the central hub for parents, swimmers, coaches, and club representatives, offering real-time information and administrative tools.

## Key Features

* **Real-time Countdown:** Live timer tracking the precise time until the start of Round 1.
* **Live League Table:** Dynamic standings powered by a database connection, automatically updating league positions as scores are entered.
* **Club Directory:** Searchable list of all 20 participating clubs, complete with venue details and integrated Google Maps directions.
* **Sponsors & Merchandise:** Dedicated section showcasing the main league sponsor (Wyvern Swimwear) and an image gallery of official 2026 merchandise.
* **Spectator Information:** Comprehensive guide for attendees, including admission pricing, parking details, and warm-up times.
* **League History:** Archive of past season results (starting from 2025).

### Admin & Representative Tools

* **Club Rep Portal:** A password-protected dashboard providing officials with access to essential documentation (Team Sheets, Rules, Gala Paperwork).
* **Score Management System:** A secure, server-side utility (`update_scores.php`) allowing the League Secretary to input and update round scores directly into the MySQL database.

## Technical Details

The project has evolved from a static site to a dynamic PHP application to handle data persistence and templating.

* **Core Logic:** PHP (v8+) used for component templating (`nav.php`) and backend logic.
* **Database:** MySQL (`cotswold_league`) stores club data and match results.
* **Frontend:** HTML5 styled with **Tailwind CSS** (via CDN).
* **Icons:** Powered by **Lucide** (via CDN).
* **Interactivity:** Lightweight Vanilla JavaScript for the countdown timer, mobile navigation, and UI interactions.
* **Security:** Basic password authentication for the admin and score update interfaces.

## Hosting & Infrastructure

This project is a personal initiative to streamline league operations. It is currently hosted using:

* **XAMPP:** Local Apache server and MySQL database for development and hosting.
* **Cloudflare Tunnels:** Securely exposes the local server to the public domain.
* **GitHub:** Version control and remote backup.

## Maintenance

Managed and maintained by **Lewis Plume** (League Secretary).

*No league funds were used for the creation or maintenance of this project.*

Feedback and historical data submissions are welcome via the contact links on the site.

---
&copy; 2026 The Cotswold Swimming League | Built by Lewis Plume