# CarGawy — Roles & Permissions

> Blueprint v0.1 — awaiting approval.

## 1. Roles

| Role slug | Name | Based on | Login area |
|---|---|---|---|
| `administrator` | Super Administrator | WP core + all `cg_*` caps | wp-admin |
| `cg_manager` | CarGawy Manager | custom | wp-admin |
| `cg_inventory_staff` | Inventory Staff | custom | wp-admin |
| `cg_inspector` | Inspection Staff | custom | wp-admin (inspection screens only) |
| `cg_legal_reviewer` | Legal Review Staff | custom | wp-admin (legal screens only) |
| `cg_editor` | Content Editor [R] | WP `editor` caps on posts/pages only | wp-admin |
| `cg_dealer_owner` | Dealer | custom | `/account/` |
| `cg_dealer_staff` | Dealer staff [P1] | custom | `/account/` |
| `cg_member` | Member (buyer **and** individual seller) [R, decision D1] | custom | `/account/` |

Guests (not logged in) can browse, search, use WhatsApp/call, submit inquiries, and favorite locally.

**Why `cg_member` instead of separate Buyer/Seller roles:** the same Egyptian user often buys and sells. One role avoids a confusing signup choice; selling limits and moderation provide control. If you prefer separate roles, the capability map below still works — only signup changes. **[Q]**

`cg_editor` is added because the blog needs someone who can publish articles without marketplace access.

## 2. Capabilities

Ownership is enforced with `map_meta_cap`: meta caps like `cg_edit_car` resolve to primitive caps depending on whether the user owns the car, belongs to its dealer, or is staff.

| Capability | Admin | Manager | Inventory | Inspector | Legal | Editor | Dealer owner | Dealer staff | Member |
|---|---|---|---|---|---|---|---|---|---|
| `cg_submit_car` (create own listing) | ✔ | ✔ | ✔ | — | — | — | ✔ | ✔ | ✔ |
| `cg_edit_own_car` | ✔ | ✔ | ✔ | — | — | — | ✔ (dealer's cars) | ✔ (dealer's cars) | ✔ (own) |
| `cg_delete_own_car` (draft/rejected only) | ✔ | ✔ | ✔ | — | — | — | ✔ | — | ✔ |
| `cg_mark_sold_own_car` | ✔ | ✔ | ✔ | — | — | — | ✔ | ✔ | ✔ |
| `cg_manage_inventory` (CarGawy-owned cars) | ✔ | ✔ | ✔ | — | — | — | — | — | — |
| `cg_publish_inventory` (skip moderation for own stock) | ✔ | ✔ | ✔ [Q] | — | — | — | — | — | — |
| `cg_view_inventory_cost` | ✔ | ✔ | — [Q] | — | — | — | — | — | — |
| `cg_edit_others_cars` | ✔ | ✔ | — | — | — | — | — | — | — |
| `cg_moderate_listings` (approve/reject/suspend) | ✔ | ✔ | — | — | — | — | — | — | — |
| `cg_feature_listings` | ✔ | ✔ | — | — | — | — | — | — | — |
| `cg_verify_dealers` | ✔ | ✔ | — | — | — | — | — | — | — |
| `cg_manage_dealer_profile` | ✔ | ✔ | — | — | — | — | ✔ (own) | — | — |
| `cg_manage_dealer_members` | ✔ | ✔ | — | — | — | — | ✔ (own) | — | — |
| `cg_request_inspection` | ✔ | ✔ | ✔ | — | — | — | ✔ | ✔ | ✔ (own car) |
| `cg_schedule_inspection` | ✔ | ✔ | — | — | — | — | — | — | — |
| `cg_perform_inspection` (assigned only) | ✔ | — | — | ✔ | — | — | — | — | — |
| `cg_approve_inspection` (issues badge) | ✔ | ✔ | — | — | — | — | — | — | — |
| `cg_request_legal_review` | ✔ | ✔ | ✔ | — | — | — | ✔ | ✔ | ✔ (own car) |
| `cg_perform_legal_review` | ✔ | ✔ [Q] | — | — | ✔ | — | — | — | — |
| `cg_view_private_files` (scoped) | ✔ | ✔ | — | ✔ (assigned) | ✔ (assigned) | — | ✔ (own uploads) | — | ✔ (own uploads) |
| `cg_view_all_leads` | ✔ | ✔ | ✔ (inventory leads) | — | — | — | — | — | — |
| `cg_view_own_leads` | ✔ | ✔ | ✔ | — | — | — | ✔ | ✔ | ✔ |
| `cg_manage_leads` (status, assign, notes) | ✔ | ✔ | ✔ (inventory) | — | — | — | ✔ (own) | ✔ (own) | — |
| `cg_view_reports` | ✔ | ✔ | — | — | — | — | own stats | own stats | own stats |
| `cg_manage_settings` | ✔ | — | — | — | — | — | — | — | — |
| `cg_view_audit_log` | ✔ | ✔ | — | — | — | — | — | — | — |
| `edit_posts` / `publish_posts` (blog) | ✔ | — [Q] | — | — | — | ✔ | — | — | — |
| `list_users` / `edit_users` | ✔ | members & dealers only [R] | — | — | — | — | — | — | — |

## 3. Hard rules (each has an automated test)

1. A member/dealer can only read or change cars where `post_author = me` or `dealer_id ∈ my dealers`.
2. Dealer users never see CarGawy inventory screens or internal cost fields (cost is stripped in REST output for non-holders of `cg_view_inventory_cost`).
3. Inspectors see only inspections where `inspector_id = me`; they cannot approve listings, legal reviews, or their own inspection outcome.
4. Legal reviewers have no inspection capability; legal approval never changes `badge_inspection_id`.
5. Only `cg_moderate_listings` can move an external listing from `pending` to `publish`.
6. Every transition listed in §2 under moderation/verification/dealer verification/lead assignment writes to `wp_cg_audit_log`.
7. A manager cannot promote a user to `administrator` (filter on `editable_roles`).

## 4. Session & account security

- Strong password policy, rate-limited login (5 attempts / 15 min per IP + username).
- 2FA mandatory for all staff roles [R] (e.g. via a small TOTP implementation or a vetted 2FA plugin **[Q]**).
- Staff sessions shorter (12 h) than member sessions (14 days "remember me").
- Front-end account pages send `Cache-Control: private, no-store` and are excluded from LiteSpeed cache.
