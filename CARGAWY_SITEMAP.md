# CarGawy — Sitemap & Page Specifications

> Blueprint v0.1 — awaiting approval. Arabic URLs use English slugs (stable, shareable, no percent-encoding) [R]. English pages live under `/en/` via Polylang [R] **[Q]**.

## 1. URL map

```
/                                   Home
/cars/                              Browse cars (filters via ?query)
/cars/{make}-{model}-{year}-{id}/   Car details
/cars/make/{make}/                  Make landing (noindex until content exists)
/cars/make/{make}/{model}/          Model landing (same rule)
/cars/location/{governorate}/       Location landing (same rule)
/sell-your-car/                     Sell your car (explainer + start wizard)
/dealer-registration/               Dealer registration
/dealers/                           Dealer directory
/dealers/{dealer-slug}/             Dealer profile + their cars
/inspection/                        Technical inspection service
/legal-review/                      Legal document review service
/about-us/
/contact-us/
/faq/
/blog/                              Blog home
/blog/{article-slug}/               Article
/blog/category/{slug}/              Category
/login/  /register/  /forgot-password/
/privacy-policy/  /terms/  /cookie-policy/  /listing-rules/
/report-listing/?car={id}
/search/?q=                         Site search (cars + articles), noindex
/account/                           Overview          ┐
/account/cars/                      My cars (tabs by status)
/account/cars/new/                  Add car wizard     │ noindex, private,
/account/cars/{id}/edit/                               │ role-aware
/account/inquiries/                                    │
/account/favorites/                                    │
/account/inspections/               Requests & results │
/account/dealer/                    Dealer profile (dealer only)
/account/dealer/team/               Dealer staff (dealer owner, P1)
/account/performance/               Stats (dealer; members get per-car stats)
/account/settings/  /account/security/                 ┘
/go/wa/{id}/  /go/call/{id}/        Tracking redirects, noindex, disallow
/wp-admin/admin.php?page=cargawy*   Internal dashboard (staff)
```

## 2. Navigation

**Header (desktop):** Logo · سيارات للبيع (Browse) · المعارض (Dealers) · خدماتنا ▾ (الفحص الفني · مراجعة الأوراق) · المدونة · [بيع عربيتك] primary button · Account icon · Language switch.
**Header (mobile):** Logo · search icon · menu. Bottom sticky bar on car details: WhatsApp · Call · Inquiry.
**Footer:** Brand + tagline · Browse by make (top 8) · Services · Company (About, Contact, FAQ, Listing rules) · Legal (Privacy, Terms, Cookies) · Social (only real links) · Contact (only owner-supplied details).

## 3. Page specifications

### Home `/`
| # | Section | Content / behaviour | Data |
|---|---|---|---|
| 1 | Header | as §2 | |
| 2 | Hero | Tagline «عربيتك تستاهل.. اختارها على نور!», sub-line, real car photo (owner-supplied) | |
| 3 | Hero search | Make, Model (dependent), Price min/max, Year, Location → submits to `/cars/?…`. On mobile: Make + Price + "More filters" | taxonomies |
| 4 | Browse by make | Logo chips, top 12 by live count | `cg_make` counts |
| 5 | Featured cars | Carousel of `is_featured` | |
| 6 | Latest cars | 8 newest | |
| 7 | CarGawy inventory | Strip, only if ≥ 4 live | |
| 8 | From individual sellers | Strip, only if ≥ 4 live | |
| 9 | Trusted dealers | Verified dealers with live car counts | |
| 10 | Why CarGawy | 4 points: real info sources, independent inspection, legal review, direct contact | |
| 11 | How it works | 3 steps for buyers / tab for sellers | |
| 12–13 | Inspection & Legal review teasers | What is and isn't checked; CTA to service pages | |
| 14 | Sell CTA | | |
| 15 | Blog | 3 latest articles | |
| 16 | Testimonials | **Hidden until real consented testimonials exist** | |
| 17 | FAQ | 6 questions, links to `/faq/` | |
| 18–19 | Final CTA, footer | | |

Sections 7, 8, 9, 16 auto-hide when there is not enough real data — the page never shows empty or fake content.

### Browse `/cars/`
Desktop: filter sidebar (inline-start) + results. Mobile: sticky top bar with "Filters (n)" button opening a full-screen sheet, and sort select. Active filter chips, result count, grid cards, pagination with page links (crawlable), empty state with "clear filters" and "save search" (P2).

### Car details
Breadcrumb · gallery · title/price/negotiable · key spec chips (year, km, transmission, fuel, location) · **verification panel** (inspection + legal, each with its own status and explanation) · seller card · contact block (sticky on mobile) · specs table · condition table with "source" column · description · safety tips · similar cars · report listing.

### Sell your car
Benefits · steps (create account → add details & photos → review within X hours **[Q]** → buyers contact you) · photo guidelines (8 angles) · requirements · FAQ · CTA starting the wizard.

**Add-car wizard (`/account/cars/new/`)** — 5 steps, autosaved draft: 1) Car (make, model, year, trim) 2) Specs & condition (declared) 3) Price & location 4) Photos (min 4 **[Q]**, max 20, drag to order) 5) Review & submit (listing rules checkbox).

### Dealer registration
Benefits · form (business name, CR number **[Q]**, governorate/city, address, phone, WhatsApp, logo, owner account) · terms · "we'll verify within X days" **[Q]**.

### Inspection / Legal review pages
Explanation · stages · checklist categories · what the report contains · **limitations & disclaimers (text from counsel [Q])** · request form · FAQ. **No inspection price is shown on these pages [C].**

### Other pages
About (only owner-supplied story, team, company info), Contact (form + only real details), FAQ (grouped: buying, selling, dealers, inspection, legal, accounts), policies (drafted by counsel **[Q]** — we do not write legal policies), 404 with search and popular makes.

## 4. Internal dashboard (wp-admin → "CarGawy")

```
CarGawy
├── Overview (KPIs + queues needing action)
├── Vehicles
│   ├── All vehicles (filters: source, status, availability, make, inspection)
│   ├── CarGawy inventory
│   ├── Pending approval  (badge count)
│   ├── Rejected / Suspended / Expired
│   └── Featured
├── Dealers (verification queue + directory)
├── Members
├── Inspections (requests · schedule · reports)
├── Legal reviews
├── Leads (pipeline view + table)
├── Reports (listings, leads, conversion, moderation time)
├── SEO (redirects · 404 monitor · defaults & templates · robots.txt)
├── Audit log
└── Settings (general, contact, limits, inspection policy, analytics IDs, privacy)
```

KPIs (all real-data backed): active listings, pending approvals, published this week, sold (30 d), members, dealers (verified/pending), inquiries (7 d / 30 d), WhatsApp clicks, call clicks, inspection requests open, inspections completed (30 d), median approval time.

## 5. Seller / dealer dashboard

| Section | Member | Dealer |
|---|---|---|
| Overview: listing counts per status, last 30 d views & contacts, action items (rejected listings, missing photos) | ✔ | ✔ (+ team) |
| My cars (tabs: Published · Pending · Drafts · Rejected · Sold) with row actions | ✔ | ✔ (+ bulk actions, search) |
| Add / edit car (wizard) | ✔ | ✔ |
| Inquiries (list, mark contacted) | ✔ | ✔ (+ assign to staff) |
| Inspections & legal reviews (request, status, report) | ✔ | ✔ |
| Favorites | ✔ | — |
| Dealer profile, team | — | ✔ |
| Performance (per-car views/contacts table, 30 d trend) | basic | ✔ |
| Settings, security | ✔ | ✔ |
