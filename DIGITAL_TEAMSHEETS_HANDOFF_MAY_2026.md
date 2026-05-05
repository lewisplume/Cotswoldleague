# Digital Teamsheets Handoff - May 2026

This note captures the full digital teamsheets workstream as it evolved in conversation and implementation, so a future LLM can pick up the thread without re-deriving the design from scratch.

## What We Built

The old workflow relied on Google Sheets teamsheets for swimmer entry, relay selection, shared visibility, and secretary notification. We built a parallel in-portal digital teamsheets module that lives inside the existing team portal codebase while leaving the legacy sheet flow available as a fallback.

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
- autosave is enabled so edits do not vanish if the user forgets to press save

### Teamsheet Builder

The event builder mirrors the Google Sheet workflow in a portal-native way:
- each individual event has one swimmer dropdown
- relays and cannons use multiple dropdowns, one per position, instead of a browser multiselect
- relay/cannon PB fields are greyed out because those events do not use PBs
- each event row has a minimise/expand control so completed events can be collapsed
- host notes and warning text remain visible in the row flow

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

The existing Google Sheet workflow is still intact while the portal version proves itself. The point was to add a safer replacement, not to break the old path before confidence is high.

### Smart Programme / Results Matcher

The downstream tools were updated so they can load either:
- a legacy Google Sheet, or
- a portal-generated digital teamsheet export

That keeps the rest of the gala pipeline usable while the teamsheet workflow migrates.

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
12. This handoff note and the monthly update log were requested to preserve the design and implementation history.

## Current Status

The digital teamsheets module is now a functioning beta feature inside the portal, with:
- full page workspace
- tabbed layout
- season-aware swimmer copy-forward
- shared gala visibility
- editable submissions with audit trail
- autosave
- relay/cannon selection fixes
- collapse controls

## Things To Watch Going Forward

- This is still a beta path, so the old Google Sheet route should remain available until the portal workflow is trusted in live use.
- Because autosave is now present, be careful when changing event navigation or tab switching so unsaved state is not accidentally dropped.
- The audit trail and shared visibility rules should stay aligned with gala membership and season scoping.
- If additional event types appear, the swimmer-picker and PB logic should be checked carefully before assuming they behave like current individual events.

