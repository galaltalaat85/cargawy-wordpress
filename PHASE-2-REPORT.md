# Phase 2 — Foundation: report

**Status:** complete locally, awaiting owner review. Staging on Hostinger not yet created (needs hPanel access).

## Scope delivered

| Item | Where |
|---|---|
| Git repository, Composer dev tooling, `.gitignore` | repo root |
| Plugin bootstrap, PSR-4 autoloader (no Composer needed in production) | `cargawy-core.php`, `includes/Autoloader.php`, `includes/Plugin.php` |
| All 15 custom tables from the approved schema, versioned installer, migration hook | `includes/Database/Schema.php`, `includes/Activation/Installer.php` |
| 8 CarGawy roles + administrator caps from the approved matrix (28 capabilities) | `includes/Permissions/Capabilities.php`, `Roles.php` |
| Managers can only manage members/dealers; cannot edit, delete or promote staff/admins | `includes/Permissions/UserManagementGuard.php` |
| Members and dealers are redirected out of wp-admin to `/account/`; admin bar hidden | `includes/Permissions/AdminAccess.php` |
| Append-only audit log with hashed IPs (raw IPs never stored) + `cargawy_audit()` | `includes/Audit/AuditLog.php` |
| Workflow engine used by all future status changes (transition table, capability, guard, audit, hook) | `includes/Workflow/StateMachine.php` |
| Settings with validation (Egyptian phone → E.164, GA4/Ads/Pixel ID formats, https-only URLs, ranges), changes audited | `includes/Settings/Settings.php` |
| Admin screens: System health (Hostinger checklist, live), Audit log, Roles & permissions, Settings | `includes/Admin/*` |
| Safe uninstall (opt-in data deletion) | `uninstall.php`, `includes/Activation/Uninstaller.php` |
| Translation template (105 strings) | `languages/cargawy-core.pot` |
| CI: WPCS + PHP syntax + PHPUnit on PHP 8.1 and 8.3 with MariaDB 10.11 | `.github/workflows/ci.yml` |
| Local site script | `bin/setup-local.sh` |

## Database changes

15 tables created (`wp_cg_vehicles`, `cg_dealer_members`, `cg_inspections`, `cg_inspection_items`, `cg_legal_reviews`, `cg_files`, `cg_leads`, `cg_lead_notes`, `cg_favorites`, `cg_car_stats_daily`, `cg_events`, `cg_audit_log`, `cg_moderation`, `cg_redirects`, `cg_404_log`). All empty. Options: `cargawy_db_version`, `cargawy_roles_version`, `cargawy_settings`, `cargawy_activated_at`.

**Deviation from the schema document:** status-like columns are `VARCHAR` instead of `ENUM`. Reason: adding a status later (e.g. a new inspection outcome) then needs no `ALTER TABLE` on a live table; allowed values are enforced in PHP by the workflow classes. The schema document will be updated to match.

## Test results (run in this environment)

| Check | Result |
|---|---|
| PHPUnit (WordPress 6.9 test suite, MariaDB 10.11, PHP 8.3) | **49 tests, 299 assertions — all pass** |
| WordPress Coding Standards (WPCS 3, WordPress-Extra + PHPCompatibility 8.1+) | **0 errors, 0 warnings** |
| PHP syntax (`php -l`) on every file | pass |
| Local site: activate → 15 tables + 8 roles | pass |
| Deactivate → tables and roles still present; reactivate | pass |
| Browser, admin: System health, Settings (save, invalid GA4 rejected with message, phone normalised to `+201012345678`), Audit log, Roles | pass, 0 JS errors |
| Browser, member `mona`: opening `/wp-admin/` redirects to `/account/` | pass |
| Browser, manager: Settings → "not allowed"; editing the administrator → "not allowed"; role dropdown offers only Member / Dealer staff / Dealer (owner) | pass |

Bugs found and fixed during testing:
1. Saving settings with a field missing from the form flagged it as invalid → now keeps the current value.
2. The very first settings save was not audited (WordPress fires `add_option_*`, not `update_option_*`) → both are now audited, with a test.

Not yet run: CI on GitHub (no remote repository yet), anything on Hostinger.

## Security notes

- All capability checks happen server-side; admin pages re-check capability on render.
- Settings page uses the Settings API (nonce + `cg_manage_settings` capability via `option_page_capability_*`).
- All SQL with variable input is prepared; table names come from a constant list.
- All output escaped (`esc_html`, `esc_attr`, `esc_textarea`, `wp_kses_post`).
- Raw IP addresses are never stored; HMAC-SHA256 with the site's auth salt.
- Audit log repository has no update/delete methods (asserted by a test).

## What the owner can check

1. Review this report and the code in `wp-content/plugins/cargawy-core/`.
2. Give hPanel access (or a Hostinger staging site) so I can install the plugin on staging and read the real System health page for the Business plan.
3. Create an empty GitHub repository (e.g. `cargawy/cargawy-wp`) so I can push and CI starts running.

## Next: Phase 3 — Vehicles & inventory

`cg_car` post type and statuses, taxonomies with seed data (makes/models, 27 governorates with cities, body/fuel/transmission), vehicle field schema and validation, admin edit screen for inventory staff, photo ordering, ownership rules in `map_meta_cap`.
