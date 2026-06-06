# Team Portal / Club Rep Portal Merge Brief

Date: 2026-06-06

## Objective

Merge the current Club Rep Portal experience into the Team Portal so the Team Portal becomes the single club-facing home.

The public navigation item labelled `Team Login` must take users directly to the Team Portal login page (`teamportal.php`). The current Club Rep Portal (`admin.php`) should no longer be the main route for club representatives.

## Current State

- `nav.php` sends desktop and mobile `Team Login` links to `admin`.
- `admin.php` is a league-password-protected representative gateway. It currently contains:
  - a link into `teamportal.php`
  - AGM invitation and AGM agenda link
  - Governance & Info / League Rules link
  - Teamsheets & Results quick links
  - Community & Support links
  - Helpful Documents links
  - Host Team Checklist with localStorage checkboxes
  - Recent Venue Updates / audit log summary
  - contact/support footer copy
- `teamportal.php` is already the club-specific, PIN-protected working portal. It currently contains:
  - club login and logout
  - club header
  - Digital Teamsheets launch/workspace
  - Gala Scoresheet links for hosting clubs
  - host venue editing
  - contact editing
  - PIN change
  - round draws/results
  - league directory and email tools

## Recommended Product Design

Use `teamportal.php` as the only club representative dashboard after login. Add a top-level in-page tab/navigation strip below the club header:

- `Overview`
- `Documents`
- `Host Checklist`
- `Directory`
- optional `Account`

Keep this as page-section tabs, not a separate login flow. A simple hash-based or button-driven section switch is enough, as long as deep links like `teamportal.php#documents` work or at least scroll to the section.

### Overview Tab

Keep the existing main operational content here:

- Digital Teamsheets launch card
- Gala Scoresheet card when relevant
- Edit Host Venues
- My Round Draws & Results

The Overview should remain the default landing view after login.

### Documents Tab

Move the Club Rep Portal resource content from `admin.php` into this section.

Include grouped document/resource panels:

1. Governance
   - League Rules 2026
   - AGM Invitation details
   - AGM Agenda link

2. Printable Gala Documents
   - Officials Sign-in (`Officials Sign-in.php`)
   - Spectator Programme (`spectator-programme.php`)
   - DQ Report Form Google Drive link
   - Timekeeper Sheet (`Timekeeper-sheets.php`)
   - Chief Timekeeper Slips (`ChiefTKSlips.php`)
   - Announcers Guide (`Announcers-guide.php`)

3. Teamsheets & Results
   - Digital Teamsheets (`digital-teamsheets.php`)
   - Team Portal scoresheet/results guidance. Do not link this back to `teamportal.php` unless it is an anchor to the relevant section.

4. Community & Support
   - WhatsApp Community
   - Facebook
   - Instagram
   - support copy/email links currently at the bottom of `admin.php`

Design expectation:

- Use the existing `glass-panel` style from `teamportal.php`.
- Avoid nesting cards inside cards.
- Use compact cards/list rows with Lucide icons.
- Ensure long document titles and Google Drive labels do not truncate awkwardly on mobile.

### Host Checklist Tab

Move the existing Host Team Checklist from `admin.php` into `teamportal.php`.

Important behavior:

- Preserve localStorage persistence, but key it per club and season so one browser shared by multiple clubs does not mix checklist progress.
- Suggested key format: `host_checklist_${season}_${clubId}_${itemId}`.
- Keep the reset behavior, but reset only the current club/season checklist keys.
- Keep every current checklist item and link:
  - League Rules
  - Teamsheets
  - Digital Scoresheet
  - Officials Sign-In Sheet
  - DQ Report Forms
  - Timekeeper Sheets
  - Chief Timekeeper Slips
  - Blank Programmes
  - Announcers Guide

### Directory Tab

The current League Directory at the bottom of `teamportal.php` should become a named tab/section rather than a large always-visible block. Preserve:

- filter by finals/round
- host venue filter
- row selection
- email selected
- copy list

### Account Tab / Side Panel

The current right-hand `Edit Team Contacts` and `Security PIN` blocks can either remain on the right of Overview or move into an Account tab. Preferred: move them into `Account` because the merged portal will otherwise be busy.

Do not remove contact editing or PIN changes.

## Routing Requirements

1. Update `nav.php`:
   - desktop `Team Login` link: `href="teamportal"`
   - mobile `Team Login` link: `href="teamportal"`

2. Leave `admin.php` in place temporarily, but demote it:
   - Option A: make it redirect logged-out and logged-in users to `teamportal.php`.
   - Option B: leave it accessible only as a legacy page, but update its copy to say the Team Portal is the main club area.

Preferred for this implementation: Option A after the moved content is verified.

3. Update internal backlinks:
   - In `teamportal.php`, replace the header back button text/link currently pointing to `admin.php` with a neutral action such as `Portal Home` / `teamportal.php`.
   - In `digital-teamsheets.php` / standalone mode, keep the return button pointing to `teamportal.php`.

## Security Requirements

- Do not weaken the club PIN authentication in `teamportal.php`.
- Do not move team-specific tools into `admin.php`.
- Do not rely on `$_SESSION['logged_in']` from `admin.php` for team portal content.
- Keep all escaped output with `htmlspecialchars()`.
- If adding URL/hash handling, do not reflect unescaped values into HTML.

## Implementation Notes

- Most of the work is HTML/PHP restructuring inside `teamportal.php`.
- Avoid changing database schema.
- Avoid deleting `admin.php` in the same change unless explicitly requested.
- If extracting reusable resource arrays, keep them in `teamportal.php` or a small include such as `portal_resources.php`; do not introduce a framework.
- After moving the checklist JavaScript, ensure it only runs when the user is logged in and when checklist elements exist.
- Run a quick search after edits for stale public-facing `admin` links labelled `Team Login`.

## Acceptance Checklist

- Public desktop navbar `Team Login` opens `teamportal.php` login.
- Public mobile navbar `Team Login` opens `teamportal.php` login.
- Logged-out Team Portal still shows club selector and 4-digit PIN login.
- Logged-in Team Portal has clear access to Documents, Host Checklist, Directory, operational dashboard, contacts, and PIN changes.
- Every resource link currently visible in `admin.php` is available from the Team Portal.
- Host Checklist checkboxes persist per club and active season.
- Digital Teamsheets flow still opens and returns correctly.
- Venue editing and contact editing still submit successfully.
- League Directory email selection/copy functions still work after being moved/tabbed.
- `admin.php` either redirects to Team Portal or clearly acts as a legacy page, with no conflicting “main portal” messaging.

## Review Plan For Checking Agent

After implementation, review:

1. `git diff -- nav.php teamportal.php admin.php digital-teamsheets.php README.md WEBSITE_GUIDE.md`
2. Search for stale routes:
   - `rg -n "href=\"admin|href='admin|Team Login|Club Rep Portal|Representative Portal"`
3. Start local server and manually test:
   - `php -S 127.0.0.1:8000`
   - `http://127.0.0.1:8000/teamportal.php`
   - `http://127.0.0.1:8000/admin.php`
4. Browser QA at desktop and mobile widths:
   - login page
   - logged-in dashboard
   - Documents tab
   - Host Checklist tab
   - Directory tab
5. Confirm no console errors from moved checklist or directory JavaScript.

