# Cotswold League - Recent System Updates & Feature Enhancements (May 2026)

This document serves as a comprehensive audit and review guide outlining the features, database architectural changes, and administrative tools implemented to scale the Cotswold League management platform from a single-year tracker into a dynamic, multi-season, fully automated system.

## 1. Multi-Season Architecture & Roll-over Capability
Previously, the system treated 2026 as the single source of truth. We have now transformed the database and application to handle infinite future (and past) seasons without losing historical data.

*   **Global State Management**: 
    *   Created a new `global_settings` table to securely track system-wide configurations, specifically the `current_season_year`.
    *   The `db.php` initiation script inherently seeks this active state, falling back to 2026 if necessary, and securely passing `$current_season_year` instantly to the rest of the application.
    *   The active-season setter in `league_admin.php` now saves the selected year with a prepared upsert, so the shared setting is updated safely even if the row already exists.
*   **Venue Schema Modernisation**: 
    *   Implemented non-destructive automatic migrations inside `db.php` and `season_data.php` to append `season_year` and 8-lane structures to the `venue_details` table inline, without manual SQL setups across different platform instances.
*   **Super Admin Toggler**: 
    *   Integrated a global "Active Season" selection tool in `league_admin.php` inside the Admin Shortcuts block. Changing this year dynamically rewires what stats, what league tables, and which venues load across the administrator portal.
    *   The season dropdown now spans the full supported admin range rather than a short fixed set of years, so future season rollover does not require code edits.
*   **Public League Standings**: 
    *   Appended `WHERE season_year = $current_season_year` across `table.php` and reporting logic to completely partition data – 2026 results stay in 2026, 2027 operates on its own clean slate.
*   **Season-Aware Admin APIs**:
    *   `gala_admin_api.php` now defaults to the active season from `db.php` and filters venue rows by both `round_number` and `season_year`, preventing mixed-season round lists.
    *   `gala_scoresheet_api.php` now validates that a venue belongs to the selected season before it creates a scoresheet, and its `find_by_venue` lookup also respects season scoping.

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
    *   The inserted gala type now uses the valid `round` enum value, fixing a MySQL enum mismatch that could block the insert on stricter databases.
*   **Direct Deletion Safety**: 
    *   Realised that testing configurations or drafting mistakes required physical database interventions to fix. Introduced an inline red Trash/Delete button to every individual Venue scheduling card.
    *   Built an explicit `DELETE FROM` endpoint connected to `admin_action = delete_venue` with a JS confirmation popup to completely remove a draw setup from existence cleanly without going into phpMyAdmin.
*   **Admin Season Control Hardening**:
    *   The active-season control in `league_admin.php` now updates the shared global setting with a prepared statement instead of raw string interpolation.
    *   The selected year is now applied immediately to the page state so the remainder of the admin screen refreshes in the new season without needing a second navigation step.

## 5. Digital Scoresheet Integration Audit
*   Conducted a complete review of the newly deployed digital scoresheet application dependencies, file allocations (including `gala_scoresheet.php`, `gala_scoresheet_api.php`, `gala_scoresheet.js`, and `sw.js`).
*   Verified that offline manifesto systems, Service Worker (PWA caching) functionality, and the database hooks are properly in order without overlapping or conflicting with the new global variables.
*   The install prompt on the first scoresheet page now explicitly tells hosts to install before gala day if the venue may not have internet.
*   Once the scoresheet id is known, the page now rewrites to a stable `gala_scoresheet.php?id=...` URL and warms the cache so the same gala can reopen offline later.

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
*   Venue edits in the portal are now restricted to the logged-in club and active season, which prevents cross-season or cross-club updates through a crafted request.

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

### 6.7 Scoresheet Offline Resilience
*   Lane assignments can now be completed offline after the gala has been opened once online, and those assignments are queued for sync when the device reconnects.
*   The service worker cache was bumped to a new version so updated scoring and prompt code reaches installed devices instead of being trapped behind stale assets.
*   The install banner copy on the first scoresheet page was rewritten to explain the offline venue workflow in plain language.

### 6.8 Dead-Heat Scoring Fix
*   Valid dead-heats now share the same place label in the UI, so tied swims display as `2nd, 2nd` rather than moving the later tied swimmer to the next ordinal.
*   The points calculation already matched gala scoring rules; this pass fixed the visible placings and cache versioning so the corrected logic is what devices actually run.

### 6.9 Navigation and Public Labels
*   `nav.php` already displays active season dynamically in header badge; this was preserved.
*   Additional stale hardcoded year strings were removed from public pages where they conflicted with active-season display behavior.
*   Season-sensitive support pages such as the announcer, officials, and timekeeper printouts now render the active season dynamically instead of hardcoding 2026.

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
*   The dropdown UI now matches the backend range, so the admin can select supported future seasons without code changes.

## 8. Scoresheet Scoring Fixes
The poolside scoring engine received two important correctness updates during this pass.

*   **Dead-Heat Placings**:
    *   Equal times now keep the same place label, including the expected gala pattern of `1st, 2nd, 2nd, 4th` in a four-team race.
    *   Points remain correct while the visible placings now match standard dead-heat rules.
*   **Offline Cache Buster**:
    *   The scoresheet shell and service worker cache now use versioned asset names, so updated scoring code is not trapped inside an older installed cache.

## 9. Season Event Propagation & Import Utility
To streamline the setup of new seasons, we implemented a sophisticated event management toolset.

*   **Template-Based Duplication**: Added a "Source Season" selector to the Duplicate Season tool, allowing administrators to use any year (e.g., 2027) as a master template for other years, even while viewing a blank season.
*   **Bulk Propagation**: Integrated multi-target support via checkboxes, enabling the event list to be pushed to 2028, 2029, and 2030 simultaneously.
*   **Smart "Empty Season" Assistant**: Implemented a dynamic UI card that appears when a season has no events, providing a one-click "Import from 2027" shortcut and other setup quick-actions.
*   **Conflict Prevention**: Enhanced the backend handler to strictly prevent overwriting existing data, providing a detailed report of successful copies vs. skipped years.
*   **Blank Season Bootstrap**: The empty-season helper now gives administrators a clearer path to seed a new year from a source season instead of starting from scratch.

## 10. Geospatial Club Directory
Modernized the club management system to include interactive mapping and location-aware data.

*   **Geocoding Integration**: Added `latitude` and `longitude` fields to the `clubs` table and implemented a geocoding service that automatically resolves coordinates from postcodes.
*   **Interactive Club Map**: Deployed a Leaflet.js-powered interactive map on the public `clubs.php` page, featuring custom markers and popups for every team in the league.
*   **Admin Coordinate Overrides**: Updated the Super Admin dashboard to allow manual fine-tuning of GPS coordinates for clubs where automated geocoding is imprecise.

## 11. Collated Teamsheet & Resource Tracking
Improved the way live digital resources are linked to specific gala venues.

*   **Dynamic Teamsheet Link Storage**: Added `teamsheet_link` support to the `venue_details` table and a centralized `finals_teamsheets.json` for championship tiers.
*   **Portal Integration**: The Team Portal previously rendered collated teamsheet buttons for every round from administrative overrides. These links have now been removed from the team-facing portal in favour of Digital Teamsheets.
*   **Legacy Cleanup**: Relocated dozens of deprecated scripts (e.g., old smartprogramme versions, debug tools) into a protected `_legacy` directory to reduce codebase clutter and security surface area.

## 12. Digital Teamsheets Portal Migration
The Google Sheets teamsheet workflow was migrated into a dedicated in-portal workspace, which is now the default route for clubs.

*   **Dedicated Workspace Page**: Added `digital-teamsheets.php` as a standalone page that reuses the team portal shell but shows only the teamsheet workspace.
*   **Portal Simplification**: `teamportal.php` now shows a compact launch card instead of the full teamsheet UI so the main dashboard stays lighter.
*   **Tabbed Interface**: Added tabs for `Swimmer List`, `Teamsheet Builder`, and `Shared Teamsheets` so each area gets full width and the workflow feels less crowded.
*   **Swimmer List Improvements**: Age groups are dropdowns with `11/U`, `13/U`, `15/U`, and `Open`; availability boxes are explicitly labelled; the swimmer name column is wider and sticky/frozen; and previous season swimmers can be copied forward and adjusted.
*   **TeamUnify Import**: Added a preview-first CSV importer for TeamUnify `Top Times` exports, mapping supported best-time events into league PB fields and leaving age groups blank when no reliable finals date can be parsed. A help pop-up now explains the TeamUnify export and CSV download steps.
*   **Swim Club Manager Import**: Added a separate preview-first XLSX importer for Swim Club Manager group PB reports, using the exported age column for league age groups and warning clubs to export with age as of the finals date.
*   **Finals Date Entry**: Added a finals date field to the Finals Results & Teamsheets Upload cards in `league_admin.php` so age groups can be calculated from DOB against the league finals date.
*   **First-Class Finals Slots**: A, B, and C Finals are now stored as proper `venue_details` rows with `round_number = 99` and `gala_type` values of `a_final`, `b_final`, and `c_final`; saving the finals date creates/updates those rows, while Auto-Gen Finals now preserves existing dates, venues, uploads, and links and only refreshes the finalist team assignments.
*   **Automatic Finals Assignment Sync**: Added `finals_sync.php` and wired it into round publishing plus the legacy manual score-save page, so A/B/C Final team slots update automatically from the latest standings after each round. Finals date/slot saves also sync immediately, and the digital teamsheet loader defensively syncs configured finals before building the dropdown. The manual Auto-Gen Finals button now acts as a fallback resync only.
*   **Finals Teamsheet Visibility**: The Teamsheet Builder now shows finals based on assigned team membership, so a club only sees the A/B/C Final it has qualified for once those team slots are populated.
*   **Digital Teamsheets Defaulted**: Removed the team-facing Google Teamsheet links and the admin collated teamsheet link fields, making the portal-based Digital Teamsheets workflow the default route for clubs.
*   **Teamsheet Builder Improvements**: Relay and cannon entries now use per-position dropdowns instead of a browser multiselect; relay and cannon PB fields are greyed out because those events do not use PBs; and each event row has a minimise/expand control so completed events can be collapsed.
*   **Contextual Teamsheet Warnings**: Removed the permanent warning column from the builder. Warnings now appear above the swimmer selector only when an entry needs attention, with an explanation and an ignore option.
*   **Availability Filtering**: Added an optional `Show Available Only` toggle in the Teamsheet Builder. It is off by default and filters swimmer dropdowns by the selected round/final availability checkboxes when enabled.
*   **Copy Round**: Added a Teamsheet Builder copy control so teams can copy selections from another saved round/final in the same season and edit from that starting point.
*   **Generate Programme & Results Matcher Links**: Restored the downstream tools around the digital workflow. Generate Programme is linked from the Teamsheet Builder once a digital teamsheet exists, and Smart Results Matcher appears on the main portal draw/result cards only when a results file is available and the club has submitted its digital teamsheet.
*   **Finals Portal Filtering Fix**: Placeholder A/B/C Final venue rows no longer appear as three hosted `Round 99` scoresheet, venue, and draw cards for the placeholder host club. Finals only show in round/results cards for clubs actually assigned to that final, the top placed club in each final (`team_1_id`) is treated as the scoresheet/venue host, and related scoresheet/teamsheet joins use the latest row to avoid duplicate portal cards.
*   **Sharing, Editing, and Safety**: Submitted teamsheets are shared automatically with the clubs in the same gala group; post-submission edits are allowed but require a reason and are written to an audit log; and autosave now covers both swimmer list and teamsheet editing.
*   **Downstream Compatibility**: `smartprogrammenew.php` and `smart-results-matcher.php` now accept portal-generated digital teamsheet exports alongside the legacy Google Sheet import path, and `digital_teamsheet_export.php` provides a CSV export route for the new workflow.

## 13. Digital Teamsheets Handoff
A dedicated handoff note was created for future maintainers and LLMs.

*   **Reference File**: Added `DIGITAL_TEAMSHEETS_HANDOFF_MAY_2026.md` with architecture notes, bugs fixed, implementation history, and watch-outs.
*   **Coverage**: The handoff file captures the conversation trail, the new feature set, the autosave behaviour, the shared-sheet model, and the relay/cannon fixes.

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
- [x] Active season dropdown now writes safely and supports the full backend year range.
- [x] Scoresheet install banner now clearly explains the offline workflow.
- [x] Scoresheet page now stays usable offline after an online open, even before lanes are assigned.
- [x] Dead-heat placings now display correctly in the scoresheet UI.
- [x] Season Event Propagation tool with bulk copy support implemented.
- [x] Smart "Empty Season" UI helper deployed.
- [x] Geocoding and interactive club mapping integrated.
- [x] Collated teamsheet link management system finalized.
- [x] Legacy codebase cleanup and directory reorganization completed.
- [x] Dedicated digital teamsheets workspace created.
- [x] Tabbed teamsheet UI implemented.
- [x] Swimmer list copy-forward and autosave added.
- [x] TeamUnify best-times CSV importer added.
- [x] Swim Club Manager group PB XLSX importer added.
- [x] Finals promoted into first-class A/B/C venue rows.
- [x] Finals team assignments now auto-sync after points are updated.
- [x] Finals teamsheet dropdowns now depend on assigned finalist teams.
- [x] Digital Teamsheets made the default club teamsheet route.
- [x] Shared teamsheet visibility and audit logging added.
- [x] Relay/cannon selection corrected to ordered dropdowns.
- [x] Relay/cannon PB fields greyed out.
- [x] Event-level minimise controls added to teamsheet rows.
- [x] Optional availability-only swimmer filtering added to the Teamsheet Builder.
- [x] Copy Round added for reusing saved teamsheet selections.
- [x] Generate Programme and Smart Results Matcher links restored for digital teamsheets.
- [x] Digital teamsheet handoff document created for future reference.
