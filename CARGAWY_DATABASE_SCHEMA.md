# CarGawy — Database Schema

> Blueprint v0.1 — awaiting approval. No tables are created in this phase. Prefix shown as `wp_`; the real `$wpdb->prefix` is used in code.

## 1. Entity relationships

```
 wp_users ──1:N── wp_posts (cg_car) ──1:1── wp_cg_vehicles ──N:1── wp_terms (make/model, body, location…)
    │                    │                        │
    │                    │                        ├──1:N── wp_cg_inspections ──1:N── wp_cg_inspection_items
    │                    │                        ├──1:N── wp_cg_legal_reviews
    │                    │                        ├──1:N── wp_cg_leads ──1:N── wp_cg_lead_notes
    │                    │                        ├──1:N── wp_cg_car_stats_daily
    │                    │                        └──N:M── wp_users via wp_cg_favorites
    │                    └── wp_posts (attachments: photos)
    │
    ├──N:M── wp_posts (cg_dealer) via wp_cg_dealer_members
    │
    └── wp_cg_files (private documents; owner_type/owner_id → inspection | legal_review | dealer)
 wp_cg_audit_log (append-only, references any entity)
 wp_cg_events (raw interaction events, pruned after 90 days)
```

## 2. WordPress-native objects

| Object | Type | Notes |
|---|---|---|
| `cg_car` | CPT, `public`, rewrite `cars`, supports title/editor/thumbnail/author/revisions | `post_author` = listing owner (member or dealer user). Description lives in `post_content`. |
| `cg_dealer` | CPT, rewrite `dealers` | Public dealer profile. Logo = featured image. |
| Post statuses | `cg_rejected`, `cg_suspended`, `cg_expired` (+ core `draft`, `pending`, `publish`, `future`) | Registered with `register_post_status`, not public. |
| `cg_make` | Hierarchical taxonomy | Parent = make (Hyundai), child = model (Elantra). Term meta: `logo_id`, `intro` (landing page). |
| `cg_body_type` | Taxonomy | sedan, hatchback, suv, crossover, pickup, van, coupe, convertible |
| `cg_location` | Hierarchical taxonomy | Governorate → city/district. Seeded with all 27 governorates. |
| `cg_fuel` | Taxonomy | petrol, diesel, hybrid, electric, natural gas (CNG) |
| `cg_transmission` | Taxonomy | manual, automatic, cvt, dct |
| Term translations | Polylang | Arabic + English names per term |

## 3. Custom tables

**Implementation note (Phase 2):** status-like columns are stored as VARCHAR instead of ENUM, so new states never need an ALTER TABLE on live data; allowed values are enforced by the workflow classes. Values listed as ENUM below are the allowed sets.

All tables: `ENGINE=InnoDB`, `utf8mb4_unicode_520_ci`, created via `dbDelta()` with a `cg_db_version` option for migrations. Money stored as integer EGP (no decimals needed for car prices). Timestamps in UTC `DATETIME`.

### 3.1 `wp_cg_vehicles` — structured listing data (1:1 with `cg_car`)

| Column | Type | Notes |
|---|---|---|
| `post_id` | BIGINT UNSIGNED PK | FK → wp_posts.ID |
| `listing_source` | ENUM('cargawy_inventory','individual_seller','dealership') | |
| `dealer_id` | BIGINT UNSIGNED NULL | FK → cg_dealer post |
| `make_term_id`, `model_term_id` | BIGINT UNSIGNED | Mirrors taxonomy for fast filters |
| `trim` | VARCHAR(80) | |
| `model_year` | SMALLINT UNSIGNED | 1980–current+1 |
| `mileage_km` | INT UNSIGNED | |
| `price_egp` | INT UNSIGNED | |
| `negotiable` | TINYINT(1) | |
| `body_term_id`, `fuel_term_id`, `transmission_term_id` | BIGINT UNSIGNED | |
| `engine_cc` | SMALLINT UNSIGNED NULL | |
| `engine_hp` | SMALLINT UNSIGNED NULL | |
| `cylinders` | TINYINT UNSIGNED NULL | |
| `drivetrain` | ENUM('fwd','rwd','awd','4wd') NULL | |
| `color_ext`, `color_int` | VARCHAR(40) | Controlled list in schema class |
| `seats`, `doors` | TINYINT UNSIGNED NULL | |
| `condition_overall`, `condition_ext`, `condition_int` | ENUM('excellent','very_good','good','fair','needs_work') NULL | **Seller-declared** |
| `paint_status` | ENUM('original','partial_repaint','full_repaint','unknown') | Seller-declared (فابريكا / رش جزئي / رش كامل) |
| `accident_declared` | ENUM('none','minor','major','unknown') | Seller-declared |
| `service_records` | ENUM('full','partial','none') NULL | |
| `owners_count` | TINYINT UNSIGNED NULL | |
| `condition_notes` | TEXT NULL | |
| `gov_term_id`, `city_term_id` | BIGINT UNSIGNED | |
| `availability` | ENUM('available','reserved','sold') DEFAULT 'available' | |
| `is_featured` | TINYINT(1) | |
| `featured_until` | DATETIME NULL | |
| `inspection_status` | VARCHAR(32) | Denormalized latest status for filtering |
| `badge_inspection_id` | BIGINT UNSIGNED NULL | **Only** set by approved inspection transition |
| `legal_status` | VARCHAR(32) | Denormalized latest legal status |
| `vin_last6` | CHAR(6) NULL | Duplicate detection; full VIN stored only in legal review (private) [R] |
| `contact_phone` | VARCHAR(20) NULL | E.164; overrides owner phone; never output in HTML until reveal |
| `published_at`, `expires_at`, `updated_at` | DATETIME | |
| `cost_egp`, `purchase_date` | INT / DATE NULL | **Internal** — CarGawy inventory only, never exposed via REST to non-staff |

**Indexes:** `(make_term_id, model_term_id, model_year)`, `(price_egp)`, `(mileage_km)`, `(gov_term_id, city_term_id)`, `(listing_source)`, `(availability, is_featured)`, `(badge_inspection_id)`. The search query joins `wp_posts` on `post_status='publish'` only.

### 3.2 `wp_cg_dealer_members`
`id` PK · `dealer_id` · `user_id` · `role` ENUM('owner','staff') · `created_at` · UNIQUE(`dealer_id`,`user_id`)

Dealer verification fields live in post meta of `cg_dealer`: `_cg_verification_status` (pending/verified/rejected/suspended), `_cg_cr_number`, `_cg_phone`, `_cg_whatsapp`, `_cg_address`, `_cg_gov_term_id`, `_cg_hours` — low volume, so post meta is fine.

### 3.3 `wp_cg_inspections`
| Column | Type |
|---|---|
| `id` | BIGINT PK |
| `car_id` | BIGINT, index |
| `requested_by` | BIGINT user |
| `inspector_id` | BIGINT user NULL |
| `status` | ENUM(not_requested, requested, scheduled, in_progress, completed, cancelled) |
| `outcome` | ENUM(passed, passed_with_observations, failed) NULL |
| `scheduled_at`, `completed_at` | DATETIME NULL |
| `location_text` | VARCHAR(190) |
| `mileage_at_inspection` | INT UNSIGNED |
| `summary_notes` | TEXT |
| `report_file_id` | BIGINT NULL → wp_cg_files |
| `approved_by`, `approved_at` | BIGINT / DATETIME NULL — must differ from `inspector_id` |
| `valid_until` | DATE NULL |
| `checklist_version` | SMALLINT |
| `created_at`, `updated_at` | DATETIME |

### 3.4 `wp_cg_inspection_items`
`id` · `inspection_id` · `category` ENUM(engine, transmission, chassis, exterior, interior, electrical, tires_brakes, accident) · `item_key` · `result` ENUM(ok, observation, issue, not_checked) · `note` TEXT · `evidence_file_ids` JSON

### 3.5 `wp_cg_legal_reviews`
`id` · `car_id` · `requested_by` · `reviewer_id` · `status` ENUM(not_requested, documents_requested, documents_received, under_review, approved, rejected, missing_documents, expired) · `documents_received` JSON · `documents_missing` JSON · `result_notes` TEXT · `public_summary` TEXT · `reviewed_at` · `valid_until` · `created_at` · `updated_at`

### 3.6 `wp_cg_files` (private)
`id` · `uuid` CHAR(36) UNIQUE · `owner_type` ENUM(inspection, legal_review, dealer, inspection_item) · `owner_id` · `uploaded_by` · `stored_path` (relative to private root, never public) · `mime` · `bytes` · `sha256` · `original_name` · `created_at` · `deleted_at`

### 3.7 `wp_cg_leads`
`id` · `car_id` · `owner_user_id` (listing owner) · `dealer_id` NULL · `buyer_user_id` NULL · `buyer_name` VARCHAR(100) · `buyer_phone` VARCHAR(20) · `buyer_email` NULL · `message` TEXT · `channel` ENUM(form, whatsapp, call, phone_reveal) · `utm_source/medium/campaign` · `status` ENUM(new, contacted, qualified, follow_up, converted, lost) · `assigned_to` NULL · `consent_at` · `ip_hash` (salted SHA-256, not raw IP) · `created_at` · `updated_at` · `anonymized_at`

Click-only channels (whatsapp/call) create a lead row without buyer PII.

### 3.8 `wp_cg_lead_notes`
`id` · `lead_id` · `author_id` · `note` · `created_at`

### 3.9 `wp_cg_favorites`
`user_id` · `car_id` · `created_at` · PK(`user_id`,`car_id`)

### 3.10 `wp_cg_car_stats_daily`
`car_id` · `day` DATE · `views` · `wa_clicks` · `call_clicks` · `phone_reveals` · `inquiries` · `favorites` · PK(`car_id`,`day`) — upserted with `INSERT … ON DUPLICATE KEY UPDATE`.

### 3.11 `wp_cg_events` (raw, short retention)
`id` · `event` VARCHAR(40) · `car_id` NULL · `user_id` NULL · `session_hash` · `meta` JSON · `created_at` — pruned after 90 days by cron.

### 3.12 `wp_cg_audit_log` (append-only)
`id` · `actor_id` · `actor_role` · `entity_type` · `entity_id` · `action` · `from_value` · `to_value` · `note` · `ip_hash` · `created_at`. No UPDATE/DELETE through the application; retention ≥ 2 years **[Q]**.

### 3.13 `wp_cg_moderation`
`id` · `car_id` · `action` ENUM(submitted, approved, rejected, suspended, reinstated, expired) · `reason_code` · `note` · `actor_id` · `created_at` — gives the seller the rejection history and drives "median time to approve" KPI.

### 3.14 `wp_cg_redirects` (SEO)
`id` · `source_path` VARCHAR(255) UNIQUE · `target` VARCHAR(255) · `code` SMALLINT (301/302/410) · `origin` ENUM('auto_slug_change','manual') · `object_type`/`object_id` NULL · `hits` · `last_hit_at` · `created_by` · `created_at`

### 3.15 `wp_cg_404_log` (SEO)
`path_hash` CHAR(40) PK · `path` VARCHAR(255) · `hits` · `first_seen` · `last_seen` · `referrer_sample` — pruned after 90 days.

SEO fields on objects are post/term meta: `_cg_seo_title`, `_cg_seo_desc`, `_cg_seo_canonical`, `_cg_seo_noindex`, `_cg_og_image_id`.

## 4. User meta

`cg_phone` (E.164, verified flag `cg_phone_verified_at`), `cg_whatsapp`, `cg_gov_term_id`, `cg_preferred_lang`, `cg_marketing_consent_at`, `cg_terms_accepted_version`.

## 5. Data lifecycle & privacy

| Data | Retention [R] — confirm with counsel **[Q]** |
|---|---|
| Leads with PII | 24 months, then anonymized (name/phone/email/message nulled) |
| Raw events | 90 days |
| Audit log | 2+ years |
| Legal documents | Until review expires + 12 months, then deleted |
| Deleted user | Listings unpublished, PII erased via WordPress Privacy Tools exporters/erasers registered by the plugin |

## 6. Plugin deactivation / uninstall

- Deactivation: unschedule cron, flush rewrites. **No data removed** [C].
- `uninstall.php`: drops tables **only** if the setting "Delete all CarGawy data on uninstall" is explicitly enabled (default off).
