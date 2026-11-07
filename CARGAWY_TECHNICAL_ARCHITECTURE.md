# CarGawy — Technical Architecture

> Blueprint v0.2 — round 1 decisions applied (in-house SEO, Hostinger Business, no Next.js migration). Legend: **[C]** confirmed · **[A]** assumption · **[R]** recommendation · **[Q]** open question.

---

## 1. Product architecture (logical)

```
                   ┌──────────────────────── Public website (cargawy-theme) ────────────────────────┐
 Buyer ──────────► │ Home · /cars/ · /cars/{slug}/ · /dealers/ · /blog/ · service pages · policies  │
                   └────────────────────────────────────┬───────────────────────────────────────────┘
                                                        │ template tags / shortcodes / REST (read)
 Member / Dealer ─► Front-end account  /account/…  ─────┤
                                                        ▼
                   ┌──────────────────────────── cargawy-core (plugin) ─────────────────────────────┐
                   │ Vehicles · Taxonomies · Moderation · Dealers · Inspections · Legal reviews     │
                   │ Leads · Favorites · Events (analytics) · Audit log · Private files · Settings  │
                   │ Roles & capabilities · REST API (/wp-json/cargawy/v1) · Admin screens · Cron   │
                   └────────────────────────────────────┬───────────────────────────────────────────┘
 Staff ──────────► wp-admin (custom CarGawy menu) ──────┘
                                                        ▼
                      MySQL: WordPress core tables + wp_cg_* custom tables · Private file store
```

**Why the split [C]:** switching or redesigning the theme must never touch marketplace data or rules. The theme only calls documented template functions (`cg_get_car()`, `cg_car_badges()`, …) and renders plugin templates it can override.

---

## 2. WordPress architecture

| Concern | Decision | Reason |
|---|---|---|
| Theme type | **Classic PHP theme + `theme.json`** for tokens (hybrid) [R] | Marketplace templates (filters, dashboards) are dynamic PHP; a pure block theme adds friction there. `theme.json` still gives editors the brand palette/typography in the block editor for blog and static pages. |
| Page building | Block editor (Gutenberg) + a handful of custom blocks registered by the theme (Hero search, Featured cars, FAQ, CTA) [R] | Lets the content team edit home/service pages without a page builder plugin. |
| Vehicles | CPT `cg_car` + 1:1 custom table `wp_cg_vehicles` [R] | Post gives slug, title, content, revisions, SEO plugin support, media relations. Custom table gives typed, indexed columns for fast faceted search. `wp_postmeta` with 10+ meta_query joins degrades badly beyond a few thousand listings. |
| Dealers | CPT `cg_dealer` (public profile) + `wp_cg_dealer_members` [R] | Profiles need public URLs/SEO; membership is relational. |
| Taxonomies | `cg_make` (hierarchical: make → model), `cg_body_type`, `cg_location` (governorate → city/district), `cg_fuel`, `cg_transmission` [R] | Gives archive URLs for landing pages and controlled vocabularies. Term IDs are mirrored into `wp_cg_vehicles` for filtering. |
| Workflows | Custom tables for inspections, legal reviews, leads, events, audit log, favorites | Non-public, high-volume, relational, need indexes and access control; posts are the wrong fit. |
| Front-end account | Rewrite endpoint `/account/{section}/` rendered by plugin templates (theme-overridable) [R] | Branded mobile UX; sellers never see wp-admin. |
| Forms | Built in the plugin (no form plugin) [R] | Forms are tightly bound to data model, permissions and lead tracking. |
| Multilingual | **Polylang** (free) for posts/pages/terms; plugin strings via `.po/.mo` [R] **[Q]** | Mature, light, works with CPTs; building i18n routing ourselves is high risk. Vehicles are *not* duplicated per language — structured fields are language-neutral and labels come from translated terms; only title/description need a translation field. |
| SEO | **In-house `Seo` module in `cargawy-core`** [C] — see §8 | Owner decision. Extends WordPress core sitemaps instead of replacing them. |
| Caching | LiteSpeed Cache (Hostinger runs LiteSpeed) [R] | Native server integration; account/dashboard pages excluded. |
| Email | SMTP via transactional provider (e.g. Brevo/Amazon SES) [R] | Hostinger PHP mail lands in spam. |
| Media | WordPress Media Library for public car photos; private store for documents (§9) | |

### Plugin count target
Launch with ≤ 4 third-party plugins: Polylang, LiteSpeed Cache, an SMTP plugin, and a backup plugin only if Hostinger backups prove insufficient. No SEO plugin [C]. Each is justified in the README.

---

## 3. Module map (inside `cargawy-core`)

| Module | Responsibility | Key hooks / classes |
|---|---|---|
| `activation` | Create/upgrade tables (`dbDelta`), add roles/caps, flush rewrites. Versioned migrations. | `CG_Installer`, `cg_db_version` option |
| `database` | Table definitions, repository classes, prepared queries | `CG_Vehicle_Repo`, `CG_Lead_Repo` … |
| `post-types`, `taxonomies` | Register CPTs/taxonomies/statuses | `init` |
| `fields` | Vehicle field schema (single source of truth for labels, types, validation, i18n) | `CG_Vehicle_Schema` |
| `users`, `permissions` | Roles, capabilities, `map_meta_cap` ownership rules | `map_meta_cap` filter |
| `marketplace` | Search/filter query builder, sorting, pagination, similar cars | `CG_Search` |
| `inventory` | CarGawy-owned stock tools (cost, purchase date — internal only) | |
| `dealers` | Dealer registration, verification, members | |
| `moderation` | Status transitions, reasons, expiry cron | `CG_Listing_State_Machine` |
| `inspection`, `legal-review` | Workflows (§6, §7) | state machines |
| `leads`, `analytics` | Lead capture, event logging, tracking redirects | `/go/wa/{car}`, `/go/call/{car}` |
| `favorites` | Save cars (logged-in; guest via localStorage then merged on login) | |
| `files` | Private document storage and authenticated streaming | |
| `api` | REST routes `cargawy/v1/*` with permission callbacks | |
| `admin` | wp-admin screens, list tables, dashboard KPIs | |
| `frontend` | Account endpoints, form handlers, template loader | |
| `audit` | Append-only log for sensitive actions | `cargawy_audit()` |

**State machines [R]:** every status change (listing, inspection, legal review, dealer verification, lead) goes through one `transition($entity, $to, $actor, $note)` method that checks the allowed transition table, checks capability, writes the change, writes the audit row, and fires `do_action( 'cargawy/{entity}/transition', … )`. This makes the badge rules and permissions enforceable in one place and testable.

---

## 4. Listing lifecycle

Two independent axes [R]:

**Moderation status** (`post_status`):

```
            submit                 approve
 draft ───────────────► pending ───────────► publish ──── (N days no update) ──► cg_expired
   ▲                      │  ▲                  │  ▲                               │
   │      reject          ▼  │ resubmit         │  │ reinstate                     │ renew (→ pending)
   └──── edit ◄──── cg_rejected                 ▼  │                               ▼
                                            cg_suspended  (manager only)       pending
 Edits to a published listing (except price/availability) ──► pending  [R]
 CarGawy inventory: staff with `cg_publish_inventory` go draft → publish directly.
```

**Availability** (`wp_cg_vehicles.availability`): `available → reserved → sold` (and `reserved → available`). Only meaningful while published.

| Public visibility | Rule |
|---|---|
| `publish` + available/reserved | Listed and indexable |
| `publish` + sold | Detail page reachable with "Sold" ribbon for 30 days, excluded from default search, then `noindex` [R] |
| `pending`, `draft`, `cg_rejected`, `cg_suspended`, `cg_expired` | Owner and staff only; 404 for the public |

Rejection reasons (controlled list) [R]: unclear photos · price unrealistic · incomplete data · duplicate · prohibited content · contact info in description · other (note required).

---

## 5. Lead & event tracking

- **WhatsApp:** button links to `/go/wa/{car_id}/` → server logs event + lead (source=whatsapp) → `302` to `https://wa.me/{owner_phone}?text={prefilled ar/en text with listing title + URL + ref code}`. Works without JS and with ad blockers.
- **Call:** "Show number" reveals via REST (rate-limited, logs `phone_reveal`); `tel:` link logs `call_click` via `/go/call/{car_id}/`.
- **Inquiry form:** server-side validated, honeypot + time-trap + rate limit, consent checkbox, stores lead, emails owner.
- **Views:** aggregated daily counter per car (`wp_cg_car_stats_daily`) — not one row per view, to keep the DB small.
- Front-end events also pushed to `dataLayer` for GA4/Meta only when consent is granted.

---

## 6. Technical inspection workflow

```
 not_requested ─► requested ─► scheduled ─► in_progress ─► completed ─┬─► passed
                      │            │            │                     ├─► passed_with_observations
                      └────────────┴────────────┴──► cancelled        └─► failed
```

| Step | Who | Rules |
|---|---|---|
| Request | Owner (seller/dealer), inventory staff, or manager | Creates `wp_cg_inspections` row. One open inspection per car. |
| Schedule | Manager | Sets inspector, date, location. Inspector notified. |
| In progress / complete | **Assigned** inspector only | Fills checklist (engine, transmission, chassis, exterior, interior, electrical, tires & brakes, accident findings), uploads evidence (private), report PDF. |
| Outcome | Inspector proposes; **manager approves** [R] | Four-eyes rule: badge needs `outcome ∈ {passed, passed_with_observations}` **and** `approved_by` set **and** `approved_by ≠ inspector_id`. |
| Badge | System | `wp_cg_vehicles.badge_inspection_id` set on approval; cleared on expiry (cron), on suspension, or when car mileage edits exceed tolerance. |
| Public view | Buyer | Summary per category (OK / Observation / Issue) + date + validity. Full report public **[Q]** or on request. |

Inspection staff **cannot** approve listings or legal reviews (no capability).

---

## 7. Legal document review workflow

```
 not_requested ─► documents_requested ─► documents_received ─► under_review ─┬─► approved ─(validity ends)─► expired
                        ▲                                                   ├─► rejected
                        └──────────────── missing_documents ◄───────────────┘
```

- Documents are uploaded to the **private store** only; reviewers see them through an authenticated viewer.
- Record: reviewer, dates, documents received (checklist from a configurable list **[Q]**), missing list, result, notes, evidence.
- Public view: "Documents reviewed on {date}: ownership licence ✔ … — CarGawy does not guarantee …" (text to be approved by legal counsel **[Q]**).
- Legal approval **never** touches `badge_inspection_id` (enforced in the state machine and covered by a test).

---

## 8. Blog & SEO architecture

| Item | Decision |
|---|---|
| Blog URLs | Posts permalink `/blog/%postname%/`; page for posts at `/blog/`; categories `/blog/category/{slug}/` |
| Car URLs | `/cars/{make}-{model}-{year}-{id}/` (id suffix keeps slugs unique and stable when the title changes) [R] |
| Landing pages | `/cars/make/{make}/`, `/cars/make/{make}/{model}/`, `/cars/location/{gov}/` — **noindex until** the term has an editorial intro ≥ 150 words and ≥ 3 live listings [R] |
| Filtered URLs | `/cars/?…` query-string filters are `noindex,follow` with canonical to the unfiltered or landing URL [R] — prevents crawl explosion |
| Pagination | Self-referencing canonicals on `/page/2/`; no `noindex` on paginated archives |
| Private pages | `/account/*`, `/go/*`, search results → `noindex`, disallowed where safe in robots.txt |
| Schema | Organization, WebSite (+SearchAction), BreadcrumbList, Article/BlogPosting, FAQPage (only on real FAQ content), `Car` (schema.org `Car` with `Offer`) on detail pages — no `AggregateRating` ever unless real reviews exist [C] |
| Hreflang | Handled by Polylang between ar/en pairs |
| Images | WebP via WordPress core, explicit width/height, descriptive alt auto-built from make/model/year + angle |

### 8.1 In-house SEO module (`includes/Seo/`) [C]

| Feature | Implementation |
|---|---|
| SEO title & meta description | Meta box on posts, pages, cars, dealers, terms (`_cg_seo_title`, `_cg_seo_desc`), with live length counter and Google-style preview. Templates with variables for cars: `{make} {model} {year} مستعملة للبيع في {city} – CarGawy` |
| Canonical | `rel=canonical` on every indexable URL; filtered `/cars/?…` canonical to the clean URL; editable override field |
| Robots meta | Per-object `noindex`/`nofollow` toggles + automatic rules (account, `/go/`, search, sold > 30 days, thin landing pages) |
| XML sitemaps | Extend WordPress core sitemaps (`wp_sitemaps_*` filters): add `cg_car` (published, not sold), `cg_dealer`, landing terms that pass the content rule; remove users sitemap; add `lastmod`; image entries for car photos |
| robots.txt | Generated via `robots_txt` filter with editable extra lines in settings; points to `/wp-sitemap.xml` |
| Open Graph & Twitter cards | Title, description, image (car cover / featured image / default brand image), `og:locale` ar_EG / en_US |
| Structured data | One JSON-LD graph per page: Organization (logo), WebSite + SearchAction, BreadcrumbList, BlogPosting, FAQPage (only from the FAQ block), `Car` + `Offer` (price EGP, availability from `availability` field) on car pages. No ratings ever. |
| Breadcrumbs | Template function `cg_breadcrumbs()` sharing one trail builder with the BreadcrumbList schema |
| Redirects | `wp_cg_redirects` table; automatic 301 when a car, post, page or term slug changes; manual redirects screen in admin; hit counter |
| 404 monitor | Logs 404 URLs (aggregated, pruned at 90 days) so editors can add redirects |
| Hreflang | Read from Polylang translation pairs when English is enabled |
| Search Console | Verification meta tag field in settings |
| Editor checks | Simple on-save warnings: missing meta description, missing image alt, title too long, no internal links in long articles |

**Why in-house works here:** the marketplace needs custom rules anyway (car schema, sold/thin-page noindex, filter canonicals); building the commodity parts alongside them keeps one source of truth. **Cost:** about one extra week in Phase 9 and ongoing maintenance when Google changes requirements. Covered by tests (QA §4).

---

## 9. Private document storage

| Option | Use |
|---|---|
| **Preferred:** directory **outside** `public_html` (e.g. `~/cargawy-private/`) [R] | Not reachable by URL at all. Hostinger allows files above `public_html` on most plans — to verify. |
| Fallback: `wp-content/uploads/cg-private/` with `.htaccess` `Require all denied` + random sub-folders | If above-root storage is unavailable. LiteSpeed honours `.htaccess`. |

Files are referenced by UUID in `wp_cg_files`, streamed by `/wp-json/cargawy/v1/files/{uuid}` after a capability + ownership check, with `Content-Disposition`, `X-Content-Type-Options: nosniff`, and every access written to the audit log. They are never added to the Media Library.

---

## 10. Hosting compatibility checklist — Hostinger Business [C]

The Business plan lists the features we rely on (LiteSpeed server, SSH, Git, cron jobs, daily backups, WordPress staging, free SSL). Exact limits (storage, inodes, PHP workers, upload size) change between plan versions, so each row is confirmed in hPanel at the start of Phase 2 and recorded here.

| Check | Required | How to verify |
|---|---|---|
| PHP version | ≥ 8.1 (target 8.2/8.3) | hPanel → PHP config |
| PHP extensions | `mysqli`, `gd` or `imagick` (WebP), `intl`, `mbstring`, `zip`, `curl`, `fileinfo` | `phpinfo()` on staging |
| MySQL / MariaDB | MySQL 8 or MariaDB ≥ 10.6 (JSON + good index support) | hPanel → Databases |
| `upload_max_filesize` / `post_max_size` | ≥ 16 MB / 64 MB | PHP config |
| `memory_limit` / `max_execution_time` | ≥ 256 MB / ≥ 60 s | PHP config |
| Storage & inodes | Plan quota vs. ~2 MB × 12 photos × listings | hPanel |
| SSL | Free SSL active on `cargawy.com` + `www` | hPanel → SSL |
| Real cron | Server cron hitting `wp-cron.php` every 5 min, `DISABLE_WP_CRON` true | hPanel → Cron jobs |
| SSH + Git | For deployment | hPanel → Advanced |
| Staging | Hostinger staging tool or subdomain `staging.cargawy.com` | hPanel |
| Backups | Daily, retention ≥ 7 days, **plus** off-site copy | hPanel → Backups |
| LiteSpeed Cache | Available | |
| Directory outside `public_html` writable by PHP (e.g. `~/domains/cargawy.com/private/`) | For private documents | Test script on staging |
| Outbound SMTP / API calls | Allowed | Test |
| Email deliverability | SPF, DKIM, DMARC on domain | DNS |
| Resource limits (CPU/entry processes) | Enough for expected traffic; else Cloud/VPS | hPanel → Resource usage |

---

## 11. Next.js project

No migration required [C]. The WordPress project is built and deployed independently; the old project is not touched.

## 12. Repository & deployment [R]

- One Git repo `cargawy-wp` containing only `wp-content/plugins/cargawy-core`, `wp-content/themes/cargawy-theme`, `composer.json` (dev tools: PHPCS + WPCS, PHPUnit, wp-phpunit), `package.json` (build: PostCSS/esbuild), `.github/workflows/ci.yml`.
- WordPress core, uploads and third-party plugins are **not** committed.
- Environments: local (wp-env/Docker) → staging (Hostinger Business staging tool or `staging.cargawy.com`) → production.
- Deploy: GitHub Actions builds assets → rsync/SSH to staging → manual approval → production. DB changes only via versioned plugin migrations.
- Secrets live in `wp-config.php` / environment, never in Git.
