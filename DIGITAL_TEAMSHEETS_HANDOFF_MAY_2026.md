# Digital Teamsheets Handoff - May 2026

This note captures the full digital teamsheets workstream as it evolved in conversation and implementation, so a future LLM can pick up the thread without re-deriving the design from scratch.

## What We Built

The old workflow relied on Google Sheets teamsheets for swimmer entry, relay selection, shared visibility, and secretary notification. We built an in-portal digital teamsheets module that lives inside the existing team portal codebase and is now the default team-facing workflow.

The main entry point is a dedicated standalone page:
- [digital-teamsheets.php](/Users/lewis/Library/CloudStorage/GoogleDrive-lewisplume@gmail.com/Other computers/My computer/cotswoldleague/digital-teamsheets.php)

That page reuses the existing portal shell but shows only the teamsheet workspace. The regular portal now carries a compact launch card instead of the full workflow, which keeps the main dashboard from becoming cluttered.

## Core User Story

The intent was to replace the “Google Sheet + macro + manual chasing” process with an experience that is:
- easier to use on the day
- season-aware
- shared automatically with the relevant clubs
- editable after submission with audit history
- compatible with the downstream programme and results tools

## Important Files

- [teamportal.php](/Users/lewis/Library/CloudStorage/GoogleDrive-lewisplume@gmail.com/Other computers/My computer/cotswoldleague/teamportal.php)
- [digital_teamsheet_api.php](/Users/lewis/Library/CloudStorage/GoogleDrive-lewisplume@gmail.com/Other computers/My computer/cotswoldleague/digital_teamsheet_api.php)
- [digital_teamsheet_export.php](/Users/lewis/Library/CloudStorage/GoogleDrive-lewisplume@gmail.com/Other computers/My computer/cotswoldleague/digital_teamsheet_export.php)
- [digital-teamsheets.php](/Users/lewis/Library/CloudStorage/GoogleDrive-lewisplume@gmail.com/Other computers/My computer/cotswoldleague/digital-teamsheets.php)
- [smartprogrammenew.php](/Users/lewis/Library/CloudStorage/GoogleDrive-lewisplume@gmail.com/Other computers/My computer/cotswoldleague/smartprogrammenew.php)
- [smart-results-matcher.php](/Users/lewis/Library/CloudStorage/GoogleDrive-lewisplume@gmail.com/Other computers/My computer/cotswoldleague/smart-results-matcher.php)
- [db.php](/Users/lewis/Library/CloudStorage/GoogleDrive-lewisplume@gmail.com/Other computers/My computer/cotswoldleague/db.php)

## Data Model

We added a parallel beta schema in `db.php`:

- `club_swimmers`
- `club_teamsheets`
- `club_teamsheet_entries`
- `club_teamsheet_audit`

Key design points:
- swimmers are unique per club and season
- teamsheets are unique per club, season, and round key
- submitted teamsheets remain editable, but edits after submission require a reason
- audit rows store change summaries and snapshots

## UI Structure

The new workspace is tabbed:
- Swimmer List
- Teamsheet Builder
- Shared Teamsheets

The tabbed interface was added because the teamsheet content is too dense for the general team portal view. Each tab now gets the full width of the page and avoids the “busy dashboard” problem.

### Swimmer List

The swimmer list is intentionally spreadsheet-like:
- the swimmer name column is wider and sticky/frozen when horizontally scrolling
- age groups are dropdowns, not typed values
- availability has clear labels for each round and final
- the list supports adding/removing swimmers inline
- previous season swimmers can be copied forward and then adjusted
- TeamUnify best-times CSV exports can be previewed and imported
- Swim Club Manager group PB XLSX exports can be previewed and imported
- autosave is enabled so edits do not vanish if the user forgets to press save

### TeamUnify Import

The TeamUnify importer was added after inspecting an untouched `Top Times` CSV export. The export format is block-based:
- swimmer header rows contain name, DOB, and TeamUnify age metadata
- event rows contain rank, event label, best time, pool/course marker, date, and meet name

The importer:
- accepts CSV upload from the Swimmer List tab
- includes a help button beside the TeamUnify import button with step-by-step export guidance
- parses TeamUnify names from `Surname, Firstname` into `Firstname Surname`
- strips the trailing `S` from best-time values
- maps league-supported events into existing PB columns
- ignores unsupported longer-distance events while reporting them in the preview
- calculates league age group from DOB when a parseable finals date exists
- leaves age group blank when no reliable finals date is available
- previews new/matched swimmers before applying anything to the active swimmer grid

Operational detail:
- the finals date can be entered on the `Finals Results & Teamsheets Upload` cards in `league_admin.php`
- saving the date there ensures A, B, and C Final venue rows exist for the active season and writes the date to all three
- the importer reads that stored `round_date` when calculating age groups

### Finals Venue Model

Finals are now treated as first-class `venue_details` rows instead of a dropdown-only special case:
- A, B, and C Final rows use `round_number = 99`
- their `gala_type` values are `a_final`, `b_final`, and `c_final`
- the finals date can be entered at AGM setup time, before the venue location or qualifying teams are known
- saving the finals date creates any missing A/B/C final rows and preserves existing rows
- the physical host/location can be edited later on the venue cards
- final team assignments are automatically synced from the current standings whenever a round is published from the gala results dashboard
- the older manual `update_scores.php` score-save route also triggers the same finals sync
- saving/creating finals slots in `league_admin.php` also syncs immediately, which covers seasons where points already existed before the finals rows were created
- `digital_teamsheet_api.php?action=load` performs a defensive sync before returning round options when finals are configured, so older active seasons are brought into line when teams open the builder
- `Auto-Gen Finals` is now only a manual resync fallback; it no longer deletes and recreates finals rows
- preserving the rows means final date, venue details, result uploads, and legacy collated teamsheet links survive when finalists are regenerated

Teamsheet visibility for finals is membership based. A final appears in a club's Teamsheet Builder only after that club is assigned into the relevant final's team slots. For example, once Swindon is assigned into `b_final`, Swindon sees `B Final`; it does not see A or C unless assigned there.

Shared implementation detail:
- `finals_sync.php` owns the reusable finals helpers
- `cotswold_sync_finals_from_standings()` ranks clubs by `round_1 + round_2 + round_3 + round_4`
- the split is top 8 to A Final, next 6 to B Final, next 6 to C Final
- the helper uses active clubs plus any club that already has results in the selected season, with missing season points treated as zero, so current-season history is preserved while retired clubs stay out of clean future seasons
- if fewer than 8 eligible clubs exist, final rows are still created but teams are not assigned

Mapped TeamUnify events:
- `25 Free`, `25 Back`, `25 Breast`, `25 Fly`
- `50 Free`, `50 Back`, `50 Breast`, `50 Fly`
- `100 Free`, `100 Back`, `100 Breast`, `100 Fly`
- `100 IM`

Unsupported rows such as `200 Free`, `400 Free`, `800 Free`, `1500 Free`, `200 IM`, and `400 IM` are deliberately ignored because the current swimmer table has no columns for them.

### Swim Club Manager Import

The Swim Club Manager importer was added separately from TeamUnify because its export is an `.xlsx` workbook rather than a CSV. The inspected `Group PB Report` format has:
- report settings at the top, including `Age on date`
- one or more club group sections
- a table header with swimmer name, age, SE number, then event PB columns

The importer:
- accepts XLSX upload from the Swimmer List tab
- reads the first worksheet in the browser with SheetJS, avoiding a PHP `ZipArchive` dependency
- parses names from `Surname, Firstname` into `Firstname Surname`
- uses the age value in the file to set `11/U`, `13/U`, `15/U`, or `Open`
- shows a warning that the age is only correct if Swim Club Manager was exported using the league finals date
- maps supported event columns into the existing PB fields
- reports unsupported longer-distance event columns in the preview
- applies imports using the same merge behaviour as TeamUnify

Mapped Swim Club Manager events:
- `25 Free`, `25 Back`, `25 Breast`, `25 Fly`
- `50 Free`, `50 Back`, `50 Breast`, `50 Fly`
- `100 Free`, `100 Back`, `100 Breast`, `100 Fly`
- `100 IM`

The Swim Club Manager importer does not calculate age from DOB because the export does not include DOB.

### Teamsheet Builder

The event builder mirrors the Google Sheet workflow in a portal-native way:
- each individual event has one swimmer dropdown
- relays and cannons use multiple dropdowns, one per position, instead of a browser multiselect
- relay/cannon PB fields are greyed out because those events do not use PBs
- individual PB fields follow the selected swimmer, clearing when no swimmer is selected and refreshing when a different swimmer is chosen
- each event row has a minimise/expand control so completed events can be collapsed
- validation warnings appear above the swimmer selector only when needed, explain the issue, and can be ignored for that event/selection
- the builder has a default-off `Show Available Only` toggle that filters dropdowns to swimmers checked as available for the selected round or final; when it is off, availability is treated as unused and does not trigger warnings
- `Copy Round` lets a club copy selections, PB snapshots, and notes from another saved teamsheet in the same season into the current builder, then edit from there

### Shared Teamsheets

Submitted teamsheets are automatically visible to the other clubs in the same gala group, so the host and visiting teams can inspect each other’s sheets without manual forwarding.

## Important Behaviour

### Autosave

We added quiet autosave for:
- swimmer list changes
- teamsheet draft changes

The autosave waits briefly after typing, then saves in the background. This reduces the risk of losing large amounts of work if someone navigates away without pressing Save.

Submitted teamsheets still require a reason for edits. That reason is retained for the editing session so repeated changes do not keep asking for it.

### Legacy Compatibility

Digital Teamsheets is now the default team-facing path. The old Google Sheet link UI has been removed from the team portal and admin venue/finals forms because the portal workflow is now the preferred experience.

The old storage columns and legacy import tools still exist in the codebase, so the Google Sheet route could be restored if needed, but clubs should no longer be directed there.

### Generate Programme / Results Matcher

The downstream tools were updated so they can load either:
- a legacy Google Sheet, or
- a portal-generated digital teamsheet export

That keeps the rest of the gala pipeline usable while the teamsheet workflow migrates.

Current portal placement:
- `smartprogrammenew.php` is linked as `Generate Programme` from the Teamsheet Builder once the selected digital teamsheet has been saved, using `digital_teamsheet_id`
- `smart-results-matcher.php` is linked from the main portal `My Round Draws & Results` cards
- the matcher button is enabled only when a results file exists and the club has submitted its digital teamsheet for that gala
- before a results file is uploaded it remains greyed out, which will be the normal 2027 behaviour until post-gala upload
- finals use `team_1_id` as the deemed host for Team Portal scoresheet and venue-edit access; placeholder `club_id` values must not make the placeholder club see all three finals
- round/results cards only show a final when that club is assigned to the relevant A/B/C Final team slots, and the portal joins only the latest related scoresheet/teamsheet row so duplicate backend records do not create duplicate cards

## Bugs Found And Fixed

1. Relay and cannon PB fields initially behaved like normal PB inputs. That was corrected so they are read-only and visually greyed out.
2. Relay selection initially used a browser multiselect pattern that allowed the wrong behaviour. That was replaced with ordered per-leg dropdowns.
3. The teamsheet page was too busy inside the main portal. That was split into a dedicated page with tabs.
4. The swimmer list needed safer season rollover. That was addressed with copy-forward support from the previous season.
5. Manual save-only behaviour risked data loss. Autosave now covers both swimmers and teamsheet edits.
6. The teamsheet needed a way to organize work event-by-event. That was handled with per-row minimise/expand controls.

## Conversation Timeline

1. The original goal was to move the Google Sheets teamsheet experience into the website.
2. We then scoped season rollover, shared visibility, audit logging, and editable submissions.
3. The UI was moved out of the cluttered team portal into its own dedicated page.
4. Tabs were added so swimmer list, teamsheet builder, and shared teamsheets each get full-width space.
5. Age groups were changed to dropdowns.
6. Availability labels were clarified.
7. The swimmer name column was widened and frozen.
8. Relay and cannon selection was fixed to use ordered dropdown positions.
9. Relay and cannon PBs were greyed out because they do not exist.
10. Event rows gained a minimise button so completed events can be collapsed.
11. Autosave was added for both swimmer list and teamsheet edits.
12. A TeamUnify CSV import preview/apply flow was added to speed up swimmer-list setup.
13. A separate Swim Club Manager XLSX import preview/apply flow was added for clubs using that platform.
14. Finals were promoted into first-class A/B/C venue rows so dates can be set early and qualifying teams can be assigned later.
15. This handoff note and the monthly update log were requested to preserve the design and implementation history.

## Current Status

The digital teamsheets module is now the default team-facing teamsheet feature inside the portal, with:
- full page workspace
- tabbed layout
- season-aware swimmer copy-forward
- shared gala visibility
- editable submissions with audit trail
- autosave
- relay/cannon selection fixes
- collapse controls
- TeamUnify best-times import preview
- Swim Club Manager group PB import preview
- first-class finals venue rows with membership-based teamsheet dropdown visibility

## Things To Watch Going Forward

- Digital Teamsheets is now the default route, but avoid deleting the old Google Sheet import code until the portal workflow has completed live-season use.
- Because autosave is now present, be careful when changing event navigation or tab switching so unsaved state is not accidentally dropped.
- The audit trail and shared visibility rules should stay aligned with gala membership and season scoping.
- If additional event types appear, the swimmer-picker and PB logic should be checked carefully before assuming they behave like current individual events.
