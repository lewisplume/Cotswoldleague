# Technical Record: Digital Scoresheet System Implementation
**Date:** May 4, 2026
**Conversation ID:** 514e9f69-e90a-413e-b03f-b92250602fd3

## 1. Project Objective
The goal was to transform the static, pre-defined gala results system into a **Flexible Digital Scoresheet** capable of handling real-world variations on the poolside. This includes dynamic team counts (4, 6, or 8 teams), absent teams, unplanned substitutions, and post-gala reconciliation for Super Admins.

---

## 2. Key Features Implemented

### 🚀 Dynamic Gala Scaling
- **Lanes 1–8 Support**: Expanded the scoresheet grid from 4 to 8 lanes to support preliminary rounds and Finals seamlessly.
- **Mark Absent**: Hosts can instantly remove a team from the setup, shifting lanes automatically.
- **Add Extra Team**: Hosts can inject a new team into the gala on-the-fly, which dynamically expands the grid and creates database entries for their results.

### 🛠️ Super Admin Tools
- **Virtual Team Swap**: A powerful tool in `admin_gala_results.php` that allows admins to "move" results between venues if a team accidentally swims at the wrong location (common in Round 1).
- **Testing Sandbox**: A dedicated, isolated environment (`gala_scoresheet.php?sandbox=1`) where admins can test all scoresheet functions without affecting live 2026/2027 data.
- **Publishing Engine**: A one-click verification system that migrates digital scoresheet data into the official league tables.

### 📥 Data Management & View Controls
- **Team Portal Integration**: Added a "Web Results" button to the Team Portal, appearing automatically once results are published.
- **Zoom Controls**: Added a "View Controls" toolbar allowing users to Zoom Out (70%), Zoom In (130%), or Reset the scoresheet display to fit more teams on a single screen.

---

## 3. Technical Architecture

### Database Schema (Key Additions)
| Table | Changes / Additions |
| :--- | :--- |
| `gala_scoresheets` | Added `status` (Draft/Published), `season_year`, `team_count`, and `venue_detail_id`. |
| `gala_teams` | Added `is_absent` flag and `lane_number`. |
| `gala_results` | Added `source_type` and `source_scoresheet_id` for virtual swaps. |
| `venue_details` | (Planned) Expansion to `gala_type` and Teams 5-8. |

### API Layer (`gala_scoresheet_api.php`)
- `substitute_team`: Swaps a team placeholder with a real club.
- `mark_absent`: Toggles the `is_absent` flag and clears lane assignments.
- `add_team`: Injects a new club into the current scoresheet.
- `create_sandbox`: Generates an isolated test gala under `season_year = 9999`.
- `swap_teams`: Handles complex multi-record transactions for the Virtual Swap tool.

### Frontend Engine (`gala_scoresheet.js`)
- **Offline Persistence**: Uses IndexedDB to save results locally in real-time.
- **Sync Manager**: Automatically pushes local changes to the server whenever a connection is detected.
- **Scoring Algorithm**: A JavaScript implementation of the original Excel formulas for points and places (handling DQs and "Too Fast" cut-offs).
- **Unified Master Grid**: Implemented a single CSS Grid with `display: contents` to enable simultaneous horizontal and vertical sticky positioning (frozen row and column headers).

---

## 4. Iterations & Bug Fixes
- **Loading Hang Fix**: Fixed a critical initialization error where the page would get stuck on "Loading Gala" due to dead JavaScript references after the removal of the Export CSV feature.
- **Sandbox Event Template**: Fixed a bug where Sandbox (Year 9999) would load an empty programme; added fallback to 2027 event template.
- **Frozen Header Alignment**: Refactored the matrix layout to use a master grid so that the "Events" column and lane headers stay perfectly aligned during scrolling.
- **Squash Rule (Zoom Fix)**: Added `min-w-0` to input containers to allow browser-default intrinsic widths to be overridden during Zoom Out.
- **Manual Setup Lock**: Added an "Edit Setup" button to prevent accidental lane changes once a gala has started.
- **DQ Visibility**: Added "Did Not Start" to the DQ reason list per user request.
- **Sync Visuals**: Added a glowing "Online/Offline" indicator to reassure hosts of their connection status.
- **Venue Draw Sync**: Fixed a bug where scoresheets weren't automatically pulling the pre-defined host venue from the season draw.

## 5. System Maturity & Scalability
- **Finals Automation**: COMPLETED. The system now features an "Auto-Generate Finals Draw" algorithm that ranks teams across 4 rounds and builds the A, B, and C final venues automatically.
- **Season Rollover**: COMPLETED & ENHANCED. The platform is fully multi-season aware. Furthermore, a **Season Propagation Tool** was added in May 2026 to allow bulk copying of event templates between years (e.g., propagating the 2027 master list to future seasons).
- **Geocoding**: COMPLETED. Club locations are now geocoded and displayed on an interactive league map.

---

## 6. File Map
- `gala_scoresheet.php`: The main poolside interface.
- `gala_scoresheet_api.php`: Backend logic for real-time adjustments.
- `gala_scoresheet.js`: The "brain" of the scoring and sync logic.
- `admin_gala_results.php`: Super Admin dashboard for verification and swaps.
- `gala_admin_api.php`: Admin-only API for cross-gala transactions.
- `admin_gala_events.php`: Event management with season propagation logic.
- `league_admin.php`: Central hub for season control, venues, and club geocoding.
- `_legacy/`: Archive of deprecated scripts replaced by this modern digital infrastructure.
