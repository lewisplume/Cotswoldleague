# Cotswold League - Recent System Updates & Feature Enhancements (May 2026)

This document serves as a comprehensive audit and review guide outlining the features, database architectural changes, and administrative tools implemented to scale the Cotswold League management platform from a single-year tracker into a dynamic, multi-season, fully automated system.

## 1. Multi-Season Architecture & Roll-over Capability
Previously, the system treated 2026 as the single source of truth. We have now transformed the database and application to handle infinite future (and past) seasons without losing historical data.

*   **Global State Management**: 
    *   Created a new `global_settings` table to securely track system-wide configurations, specifically the `current_season_year`.
    *   The `db.php` initiation script inherently seeks this active state, falling back to 2026 if necessary, and securely passing `$current_season_year` instantly to the rest of the application.
*   **Venue Schema Modernisation**: 
    *   Implemented non-destructive automatic migrations inside `db.php` and `season_data.php` to append `season_year` and 8-lane structures to the `venue_details` table inline, without manual SQL setups across different platform instances.
*   **Super Admin Toggler**: 
    *   Integrated a global "Active Season" selection tool in `league_admin.php` inside the Admin Shortcuts block. Changing this year dynamically rewires what stats, what league tables, and which venues load across the administrator portal.
*   **Public League Standings**: 
    *   Appended `WHERE season_year = $current_season_year` across `table.php` and reporting logic to completely partition data – 2026 results stay in 2026, 2027 operates on its own clean slate.

## 2. Dynamic 8-lane Finals Support
In order to host 8-lane showcase finals, the fundamental standard 4-lane schema had to be heavily upgraded both visually and mechanically.

*   **Database Scaling**: 
    *   Dynamically added `team_5_id`, `team_6_id`, `team_7_id`, and `team_8_id` to store the extended finalize draw without impacting legacy 4-team round logic.
*   **UI Enhancements (`league_admin.php`)**: 
    *   The "Competing Teams (Draw)" editor inside the Host Venues tab used to statically loop exact 4 times `for($t=1; $t<=4; $t++)`.
    *   We overhauled this loop so that if the venue's round is flagged as Finals (`round_number == 99`), it automatically unlocks and displays all 8 dropdown selectors. Standard rounds still default to locking at 4.
*   **Robust Backend Persistence**: 
    *   The `update_venue` POST action block was heavily upgraded to capture parameters for all 8 potential dropdowns. The prepared statement execution was fully restructured to properly submit these null placeholders or assigned variables safely into the database avoiding stale data overlaps.
*   **Cascade Safety**: 
    *   Updated the club deletion logic to strictly ensure that if a club assigned between lanes 5 to 8 is removed from the broader directory, they are successfully scrubbed from those final draws.

## 3. Finals Auto-Generation Logic
Moving away from a statically bound HTML structure into pure data-driven automation.

*   **Automated Standings Generation**: 
    *   Built the "Auto-Generate Finals" tool that automatically reviews points from Rounds 1 through 4 inside the active season.
    *   It intelligently calculates standings to draft an 'A Final' (Top 8 teams), 'B Final' (Next 6 teams), and 'C Final' (Bottom 6 teams).
*   **Draw Staging**: 
    *   It seamlessly compiles these rankings directly into new rows inside the `venue_details` table assigned to round `99`. 

## 4. Draft & Season Management Toolkit
Spinning up an entirely blank 2027 season resulted in an empty page. We needed a UI toolkit to allow the admin to populate the database themselves safely.

*   **On-the-fly "Add Venue" Button**: 
    *   Created a direct UI tool positioned next to the 'Auto-Gen Finals' button allowing administrators to generate a new blank Venue Card. 
    *   It includes a drop-down allowing the Admin to choose exactly which slot to create (e.g., Round 1 Slot, Round 2 Slot, Finals Slot).
    *   The backend triggers an `INSERT INTO venue_details` generating the row and pinning it precisely to the toggled `$current_season_year`.
*   **Direct Deletion Safety**: 
    *   Realised that testing configurations or drafting mistakes required physical database interventions to fix. Introduced an inline red Trash/Delete button to every individual Venue scheduling card.
    *   Built an explicit `DELETE FROM` endpoint connected to `admin_action = delete_venue` with a JS confirmation popup to completely remove a draw setup from existence cleanly without going into phpMyAdmin.

## 5. Digital Scoresheet Integration Audit
*   Conducted a complete review of the newly deployed digital scoresheet application dependencies, file allocations (including `fetch_sheet.php`, `gala_scoresheet.php`, `gala_scoresheet_api.php`, and the `gala_scoresheet.js` worker logic).
*   Verified that offline manifesto systems, Service Worker (PWA caching) functionality, and the database hooks are properly in order without overlapping or conflicting with the new global variables.

## 6. Season Isolation Hardening (Post-Implementation Alignment Pass)
After the initial multi-season release, an additional hardening pass was completed to ensure all active pages and APIs are consistently tied to the selected `current_season_year`.

### 6.1 Core Clarification: One Database, Multiple Season Slices
*   Confirmed architecture uses a **single MySQL database** with season-partitioned rows (`season_year`) and not one physical database per year.
*   Changing Active Season updates **read/write routing** to that selected year.
*   Historical years remain intact and queryable when selected.

### 6.2 Public Season Draw Alignment
*   `season-draw.php` was updated to load points, finals calculations, and venue rows by active season:
    *   Results queries now include `WHERE r.season_year = $active_season_year`.
    *   Venue query now includes `WHERE vd.season_year = $active_season_year`.
*   Public-facing text labels that were hardcoded to 2026 were updated to dynamic season values.
*   Finals date badges and footer year labels were changed to dynamic season display.

### 6.3 Shared Draw Loader (`season_data.php`) Season Filtering
*   The shared season draw builder now fetches venues by both round and season:
    *   `WHERE vd.round_number = $i AND vd.season_year = $current_season_year`
*   Team extraction was expanded to include finals lanes:
    *   Added support for `team_5_id` through `team_8_id` in draw composition.
*   This ensures all downstream pages using `$season_draw` inherit correct season scoping.

### 6.4 Team Portal Season Safety + Teamsheet Link Routing
*   `teamportal.php` was hardened to avoid cross-season leakage.
*   Hosted venues list now filters by active season:
    *   `vd.club_id = ? AND vd.season_year = ?`
*   Competing draw cards now filter by active season and include all eligible lanes:
    *   Added season condition plus team lane checks through `team_8_id`.
*   Draw card SQL joins were expanded to resolve club names for `team5_name` through `team8_name`.
*   Portal rendering now lists competing teams from lanes 1-8 (where present).
*   Teamsheet copy text was updated from hardcoded “2027” to dynamic active-season wording.

### 6.5 Scoresheet API Active-Season Defaults
*   `gala_scoresheet_api.php` was aligned to use active season defaults (`$active_season_year`) instead of hardcoded 2027 in operational flows.
*   Updated endpoints:
    *   `events` default season source
    *   `create` default `season_year`
    *   `find_by_venue` default season source
*   Sandbox template event resolution now uses active season event definitions for consistency.

### 6.6 Scoresheet Team Auto-Population Extended for Finals
*   Scoresheet creation from venue draw (`action=create`) now imports participants from lanes 1-8 (where present), not only lanes 1-4.
*   This ensures `gala_teams` and prebuilt `gala_results` rows align with finals structures and future 8-lane configurations.

### 6.7 Navigation and Public Labels
*   `nav.php` already displays active season dynamically in header badge; this was preserved.
*   Additional stale hardcoded year strings were removed from public pages where they conflicted with active-season display behavior.

## 7. Admin Workflow Updates for Future Seasons
Further improvements were made to support your chosen operating model: activate a future season and build that season manually from scratch.

### 7.1 Host Venue Drafting Model
*   Confirmed and enabled workflow:
    *   Set year (e.g., 2027, 2028, 2029) as Active Season
    *   Add venue slots round-by-round via Host Venues tab
    *   Fill host, teams, timings, venue details, and teamsheet links directly
*   Venue records created in this mode are explicitly tied to selected active year.

### 7.2 Event Creation Season Binding
*   `league_admin.php` event creation (`add_event`) now binds to `$current_season_year` instead of a fixed year.
*   This ensures new event definitions are always created in the currently active season.

### 7.3 Year Range Behavior
*   Active Season setter validates years up to 2099.
*   Current dropdown UI displays 2026-2035 options; backend supports wider range.

## 8. Functional Outcome Summary (Current State)
With all completed changes applied, season rollover behavior now works as follows:

*   Changing Active Season does **not** wipe any data.
*   The system reads/writes only the selected season slice for:
    *   Host venues
    *   Season draw calculations and display
    *   League table standings
    *   Team portal draw cards and venue teamsheet links
    *   Scoresheet creation, lookup, and event loading
*   New seasons remain intentionally empty until you create venues/events/results for that year.
*   2026 remains preserved and unaffected while future seasons are prepared.

---
**Summary Checklist**
- [x] Multi-Season capability integrated into front & backend.
- [x] 8-Lane venue backend architecture supported.
- [x] 8-Lane UI Dropdowns dynamic generation complete.
- [x] Automated Finals Generation algorithm built and mapped.
- [x] Admin UI "Add New Venue Slot" button created for future drafts.
- [x] Admin UI "Delete Venue Slot" button created for safety and cleanup.
- [x] Audited Digital Scoresheet infrastructure successfully.
- [x] Public season draw now uses active season filters.
- [x] Team portal venue/draw queries now enforce active season.
- [x] Team portal draw cards now support 8-lane finals display.
- [x] Scoresheet API defaults aligned to active season.
- [x] Scoresheet creation now auto-loads team lanes 1-8.
- [x] Residual hardcoded public season labels removed/aligned.