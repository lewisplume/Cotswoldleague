# Cotswold League operations runbook

**Baseline:** 10 August 2026  
**Applies to:** the application in this repository  
**Risk register:** `CODEBASE_AUDIT_2026-08-10.md`

## Safety boundaries

- The live database is authoritative. Do not run imports, migrations, result publication, geocoding or destructive tests merely to verify a deployment.
- Keep database dumps, credentials and personal-data exports outside the repository and outside the web document root.
- Do not deploy a credential, schema, offline-queue or eligibility-rule change without its staged checks. Those items can affect club access or accepted meet data.
- Preserve an encrypted pre-deployment database backup and a copy of the previous application release so rollback does not depend on Git history alone.

## Required configuration

Supply these through the host environment or a protected local configuration outside public routing:

- `COTSWOLD_DB_HOST`
- `COTSWOLD_DB_USER`
- `COTSWOLD_DB_PASS`
- `COTSWOLD_DB_NAME`
- `COTSWOLD_LEAGUE_PASSWORD`
- `COTSWOLD_SUPER_ADMIN_PASSWORD`

If HTTPS is terminated by a reverse proxy, set `COTSWOLD_TRUSTED_PROXY_IPS` to a comma-separated list of the proxy source IP addresses seen by PHP. Forwarding headers from all other addresses are ignored. Prefer HTTPS between the proxy and Apache so secure-cookie detection does not depend on forwarding headers.

## Pre-deployment checks

1. Review `git status --short` and confirm every changed/deleted file is intended.
2. Run `bash tests/run.sh`.
3. Confirm no SQL, credential files or new uploads are staged.
4. Test against an isolated database where writes are needed:
   - club login, contact update and POST logout;
   - administrator login, club edit and logo upload;
   - scoresheet load, offline entry, reconnection, submission, verification and publication;
   - digital swimmer/teamsheet save and submission;
   - active-season homepage, spectators, draw and table output;
   - representative workbook variants in Results Matcher and Smart Programme.
5. Verify the public edge returns the expected CSP, HSTS, frame, MIME and request-ID headers. Confirm a request sent directly to the origin cannot spoof HTTPS with `X-Forwarded-Proto`.

## Deployment and rollback

1. Put the application into the site's normal short maintenance process if files cannot be swapped atomically.
2. Deploy application files without copying local config, backups, test fixtures or scratch data into public routing.
3. Confirm `db_backups/`, `_legacy/`, `scratch/`, `scripts/`, `Documents/`, Markdown, SQL and executable uploads return HTTP 403/404 from outside the network.
4. Run read-only smoke checks first. Do not verify/publish a gala as a smoke test.
5. If club login, active-season data, assets, CSRF submission or offline reopening fails, restore the prior application release. Restore the database only if a separately approved data migration actually changed it.

## Backup and restore

- Backups must be encrypted, access-controlled, retained for an agreed period and written outside Git/web storage.
- Record database name, creation time, application revision, checksum and encryption key custodian separately.
- Test restoration periodically into an isolated database. A successful dump command is not proof of a usable restore.
- Deletion from the current worktree does not remove a historic dump from Git history or previous clones.

## Season rollover

1. Create/copy the new season's event definitions using the administrator tool and verify all 53 events and A-final variations.
2. Load the new draw and venue dates explicitly; public pages no longer substitute historic operational data.
3. Check each public query with the new active season before switching `current_season_year`.
4. Confirm the homepage round-one date, spectator label, draw, standings, finals venues and unpublished-final messages.
5. Switch the active season only after the checks above and retain the previous season in the history/archive path.

## Club coordinate maintenance

Public visits never geocode or alter club rows. After an administrator changes postcodes, run from the command line:

```sh
php scripts/geocode_clubs.php
```

The command verifies TLS, uses bounded timeouts, validates UK coordinates, rate-limits requests and returns a JSON success/failure summary. Review failures; do not repeatedly run it against invalid postcodes.

## Workbook compatibility

- `workbook_schema.json` is the supported machine-readable contract.
- Legacy unversioned workbooks remain accepted after structural validation; warnings are emitted in the browser console.
- New templates should contain a hidden `_COTSWOLD_META` sheet with `A1=COTSWOLD_WORKBOOK_SCHEMA` and `A2=1.0`.
- Preserve the original workbook. Record the SHA-256 compatibility report before relying on imported output for official results.

## Security and incident response

If backup, credential, swimmer, teamsheet or result exposure is suspected:

1. Contain public access without deleting evidence.
2. Preserve relevant Apache/Cloudflare/Git hosting logs and identify the affected time window/data classes.
3. Rotate affected credentials through a coordinated club/admin process.
4. Notify the league's data-protection decision maker and assess notification duties.
5. Record facts, decisions, affected records and remediation; do not claim absence of access solely because no directory listing existed.

## Known staged work

Shared/plaintext credentials, runtime database migrations, full teamsheet eligibility enforcement, offline queue ownership/conflict handling, relational constraints, finals allocation rules and formal privacy retention/deletion remain staged findings. Consult the audit before changing them.
