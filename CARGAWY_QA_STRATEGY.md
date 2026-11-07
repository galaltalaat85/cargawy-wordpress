# CarGawy — QA Strategy

> Blueprint v0.2 — round 1 decisions applied.

## 1. Test layers

| Layer | Tool | Runs | Covers |
|---|---|---|---|
| Static analysis | PHPCS + WordPress Coding Standards, PHPStan (level 6) with WP stubs, ESLint | Every push (CI) | Escaping/sanitizing sniffs, nonce sniffs, prepared SQL sniffs |
| Unit / integration | PHPUnit + wp-phpunit (real WP + test DB) | Every push | State machines, capabilities, repositories, search query builder, badge rules |
| REST permission tests | PHPUnit | Every push | Every route × every role → expected 200/401/403 |
| End-to-end | Playwright (Chromium, mobile + desktop viewports, `ar` and `en`) | Before each staging sign-off | Critical journeys |
| Visual regression | Playwright screenshots at 360/768/1440 × RTL/LTR | Phase 6 onwards | Theme consistency |
| Accessibility | axe-core in Playwright + manual keyboard/screen-reader pass (TalkBack, VoiceOver) | Phase 6, 11 | WCAG 2.1 AA |
| Performance | Lighthouse CI on staging, Query Monitor, k6 load test with 10k seeded listings | Phase 6, 11 | LCP, query time |
| Security | WPScan, manual review of every form/REST/upload, OWASP ZAP baseline on staging | Phase 11 | |
| Backup/restore | Restore staging from yesterday's backup into a clean site | Phase 11, then quarterly | |

## 2. Marketplace test cases

| ID | Case | Expected |
|---|---|---|
| M-01 | Member registers with valid Egyptian mobile + email | Account `cg_member`, verification email sent |
| M-02 | Dealer registers | User `cg_dealer_owner`, `cg_dealer` post with `pending` verification; no public profile yet |
| M-03 | Member submits car | `post_status=pending`, moderation row `submitted`, staff notified |
| M-04 | Manager approves | `publish`, appears in `/cars/` and its make/location archives |
| M-05 | Manager rejects with reason | `cg_rejected`, 404 for public, reason visible to owner |
| M-06 | Owner edits published car's description | Returns to `pending` |
| M-07 | Owner edits only price | Stays `publish`; audit row records old/new price |
| M-08 | Member A opens `/account/cars/{B's id}/edit/` | 403; REST PATCH → 403 |
| M-09 | Dealer staff edits dealer car | Allowed; edits car of another dealer → 403 |
| M-10 | Inventory staff publishes CarGawy car | Direct publish, `listing_source=cargawy_inventory` |
| M-11 | Dealer opens inventory admin screen | Denied; cost fields absent from any REST response |
| M-12 | Listing not updated for N days | Cron moves to `cg_expired`, owner emailed |
| M-13 | Mark sold | Excluded from default search; detail shows "Sold"; noindex after 30 days |
| M-14 | Exceed listing limit | Clear error on submit, no draft lost |

## 3. Verification test cases

| ID | Case | Expected |
|---|---|---|
| V-01 | Approve listing | `badge_inspection_id` stays NULL |
| V-02 | Request inspection | Inspection row `requested`; second open request rejected |
| V-03 | Unassigned inspector tries to complete | 403 |
| V-04 | Assigned inspector completes with outcome passed | No badge yet (awaits approval) |
| V-05 | Inspector approves own inspection (even with manager role) | Rejected — approver must differ |
| V-06 | Manager approves passed inspection | Badge set; card and detail show "Checked by CarGawy" with date |
| V-07 | Outcome failed + approval | No badge; detail shows nothing misleading (owner chooses whether to show report **[Q]**) |
| V-08 | Legal review approved | Legal chip shown; inspection badge unchanged |
| V-09 | Legal reviewer calls inspection endpoint | 403 |
| V-10 | Inspection validity passes | Cron clears badge, status shows "expired" |
| V-11 | Any transition | Audit log row with actor, from, to |
| V-12 | Guest requests private file URL | 403/404; file path not guessable; not in Media Library |

## 4. Website test cases

Home renders with/without enough data (sections auto-hide) · hero search builds correct URL · filters combine (make+price+governorate+transmission) and persist across pagination/back · sort orders correct incl. ties · pagination links crawlable · car details show correct data and source labels · WhatsApp link targets the right owner number and contains listing title/URL · inquiry form validates, rate-limits, emails owner · blog home/category/article/TOC/related cars · RTL and LTR mirror correctly (icons, chevrons, breadcrumbs, pagination) · no horizontal scroll at 360px · 404 page.

### SEO module test cases
Title/description fallbacks per object type · canonical on filtered `/cars/?…` points to clean URL · `noindex` on account, search, `/go/`, sold > 30 days, thin landing pages · core sitemap includes published cars and excludes pending/sold/noindex · robots.txt lists sitemap · JSON-LD validates (Rich Results test) and never contains ratings · changing a car/post slug creates a 301 and old URL resolves · redirect loops rejected on save · 404s are logged and aggregated · OG image falls back to brand image · **no inspection price appears anywhere** (string scan of templates, FAQ and schema).

## 5. Security test cases

Unauthenticated access to `/account/*` redirects to login · every REST route without nonce → 401/403 · IDOR on cars, leads, files, inspections · upload of `.php` renamed `.jpg`, SVG with script, oversize file → rejected · XSS payloads in title/description/name rendered escaped · SQL injection in filter params → no error, safe results · CSRF on all POST forms · rate limit on login, inquiry, phone reveal · user enumeration via `/?author=` and REST `/wp/v2/users` blocked · staff 2FA enforced.

## 6. Technical test cases

No PHP notices/warnings with `WP_DEBUG` on · no JS console errors · plugin activate/deactivate/reactivate without data loss · switch to a default theme → marketplace data intact, plugin templates fall back · migrations idempotent · cron jobs run via server cron · backup restore drill.

## 7. Exit criteria per phase

All P0 tests for the phase pass in CI and on staging · no open critical/high security findings · Lighthouse mobile performance ≥ 90 and accessibility ≥ 95 on key templates (from Phase 6) · documentation updated · owner sign-off recorded.

## 8. Device matrix

Android Chrome (360×800, mid-range), iPhone Safari (390×844), iPad (768×1024), laptop 1366×768, desktop 1440×900+. Arabic and English on each.
