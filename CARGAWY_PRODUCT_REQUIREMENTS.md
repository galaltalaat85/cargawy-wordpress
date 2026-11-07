# CarGawy — Product Requirements

> Status: **Blueprint v0.2 — owner answers of round 1 applied.** No code has been written.
> Legend used across all documents:
> - **[C] Confirmed** — stated in the owner's brief.
> - **[A] Assumption** — inferred; needs confirmation.
> - **[R] Recommendation** — our proposal, with the reason.
> - **[Q] Open question** — blocks a decision; listed in §12.

---

## 0. Inspection of available project files

| Item | Found? | Consequence |
|---|---|---|
| Official logo | **Yes** — round 1 (1254×1254 raster, circular badge) stored at `brand/cargawy-logo-original.webp` | Used in demo header and footer. Vector + horizontal versions still recommended (see DESIGN_SYSTEM §2). |
| Brand guidelines | **No** | Palette and tagline are taken from the brief [C]. Fonts are taken from the brief [C]. |
| Previous Next.js project | Not needed **[C]** (owner, round 1) | No migration. WordPress build starts clean. |
| Hosting details | **Hostinger Business** **[C]** | Checklist in TECHNICAL_ARCHITECTURE §10 updated; limits still verified in hPanel at Phase 2. |
| Design references (homepage, listing, dashboard) | Links only (not files) | Used as loose UX direction; nothing copied. |

---

## 1. Executive summary

CarGawy is a **hybrid used-car marketplace for Egypt** [C] combining three supply sources in one catalog:

1. **CarGawy inventory** — cars owned/managed by CarGawy staff.
2. **Individual sellers** — private owners listing one or a few cars.
3. **Dealerships** — verified showrooms managing a stock of cars.

Its differentiator is **trust**: two independent, auditable verification services — **technical inspection** and **legal document review** — whose results are shown honestly per listing. A buyer always sees *what CarGawy checked, what it did not check, and where each piece of information came from*.

The product is built on WordPress with a custom plugin (`cargawy-core`, all business logic) and a custom theme (`cargawy-theme`, presentation only) [C]. Arabic RTL is the primary experience; English LTR is secondary [C].

**Success at launch** [R] is measured by: approved listings live, buyer leads generated (inquiries + WhatsApp + calls) per listing, median moderation time, inspection requests, and seller re-listing rate.

---

## 2. Personas

| Persona | Goal | Main pain today | What CarGawy gives them |
|---|---|---|---|
| **Buyer (Ahmed, 32, Cairo)** | Buy a reliable used car within budget | Fake "فابريكا" claims, hidden accidents, paperwork problems | Filters, transparent condition sources, inspection & legal review status, direct contact |
| **Individual seller (Mona, 45, Giza)** | Sell her car quickly at a fair price | Spam calls, low-quality classifieds | Guided listing form, moderation, optional inspection to raise trust |
| **Dealer (showroom in Nasr City)** | Move stock, get qualified leads | Listing on many sites, no lead tracking | Dealer profile, bulk inventory management, lead inbox, basic performance |
| **CarGawy staff** | Run inventory, moderate, inspect, review documents | Manual spreadsheets | Role-based internal dashboard with audit trail |

---

## 3. Listing sources [C]

Stored as a controlled value `listing_source ∈ {cargawy_inventory, individual_seller, dealership}`.

| Capability | CarGawy inventory | Individual seller | Dealership |
|---|---|---|---|
| Who creates | Inventory staff | Registered member | Dealer owner / dealer staff |
| Needs moderation before public | No (staff are trusted) [R] — but a "publish" capability is still required | **Yes** [C] | **Yes** [C] (per listing; trusted dealers may get auto-approve later [R]) |
| Can be featured | Yes | Yes (manager decides) | Yes (manager decides) |
| Can be inspected | Yes | Yes (on request) | Yes (on request) |
| Max active listings | Unlimited | **[Q]** suggested 3 | **[Q]** plan-based |
| Contact routed to | CarGawy sales line | Seller's phone | Dealer's phone |

---

## 4. Functional requirements (by module)

Priority: **P0** = launch blocker, **P1** = launch-desirable, **P2** = post-launch.

### 4.1 Catalog & search
| ID | Requirement | Pri |
|---|---|---|
| CAT-01 | Browse `/cars/` with pagination (24/page) | P0 |
| CAT-02 | Filters: make, model (dependent on make), year range, price range, mileage max, transmission, fuel, body type, governorate/city, seller type, inspection status, CarGawy inventory only, featured | P0 |
| CAT-03 | Filters combine with AND across fields, OR within a multi-select field; state lives in the URL query string so results are shareable and back-button safe | P0 |
| CAT-04 | Sort: newest, price ↑, price ↓, mileage ↑, newest model year | P0 |
| CAT-05 | Result count and active-filter chips with one-tap removal | P0 |
| CAT-06 | Sold/reserved listings shown with a status ribbon; sold cars excluded from default results [R] | P1 |
| CAT-07 | Saved searches with alerts | P2 |

### 4.2 Car details
| ID | Requirement | Pri |
|---|---|---|
| CAR-01 | Gallery (ordered images, swipe on mobile, optional video) | P0 |
| CAR-02 | Price, negotiable flag, year, mileage, key specs row | P0 |
| CAR-03 | Full specs and condition, each condition field labelled with its **information source**: *seller declaration* vs *CarGawy inspection* | P0 |
| CAR-04 | **Verification panel** showing technical inspection and legal review as two separate statuses, with "what was / was not checked" | P0 |
| CAR-05 | Seller card (type, name/dealer, location, member since) — phone hidden until tap [R] (reduces scraping) | P0 |
| CAR-06 | WhatsApp / call / inquiry form; each action logged as a lead event | P0 |
| CAR-07 | Similar cars (same model, then same body+price band) | P1 |
| CAR-08 | Share, report listing | P1 |

### 4.3 Seller & dealer
| ID | Requirement | Pri |
|---|---|---|
| SEL-01 | Register / login with Egyptian mobile number + email; email verification [R]; phone OTP **[Q]** (needs SMS provider) | P0 |
| SEL-02 | Multi-step "Add car" wizard with draft autosave | P0 |
| SEL-03 | Status tracking: draft → pending → published / rejected (with reason) | P0 |
| SEL-04 | Edit rules: editing a **published** listing's price is instant; editing anything else returns it to pending review [R] | P0 |
| SEL-05 | Mark as sold / reserved | P0 |
| SEL-06 | Inquiry inbox | P0 |
| DLR-01 | Dealer registration with business details, logo, commercial registry number **[Q]** | P0 |
| DLR-02 | Dealer verification (manager reviews documents) separate from per-listing moderation | P0 |
| DLR-03 | Dealer public profile page `/dealers/{slug}/` | P0 |
| DLR-04 | Dealer staff sub-accounts | P1 |
| DLR-05 | Performance: views, leads, WhatsApp clicks per listing (last 30 days) | P1 |
| DLR-06 | CSV bulk import | P2 |

### 4.4 Moderation
| ID | Requirement | Pri |
|---|---|---|
| MOD-01 | Pending queue sorted by submission time with source filter | P0 |
| MOD-02 | Approve / reject with a reason from a controlled list + free note | P0 |
| MOD-03 | Suspend a live listing (reports, fraud) | P0 |
| MOD-04 | Duplicate detection hint (same VIN-last-6 / same photos hash / same phone + model) [R] | P1 |
| MOD-05 | Auto-expire listings after N days without update **[Q]** suggested 60 | P1 |

### 4.5 Verification (see dedicated workflows in TECHNICAL_ARCHITECTURE §6–7)
| ID | Requirement | Pri |
|---|---|---|
| VER-01 | Inspection lifecycle with checklist, evidence, report, outcome | P0 |
| VER-02 | "Checked by CarGawy" badge **only** after a completed, approved inspection | P0 |
| VER-03 | Legal review lifecycle, independent of inspection | P0 |
| VER-04 | Inspection validity period after which the badge is hidden **[Q]** | P0 |
| VER-05 | Audit trail of every status change | P0 |
| VER-06 | Inspection booking with scheduling | P1 — **no price shown anywhere on the site [C]** |

### 4.6 Leads & analytics
| ID | Requirement | Pri |
|---|---|---|
| LEAD-01 | Record inquiry form, WhatsApp click, call click with car, owner, source, timestamp | P0 |
| LEAD-02 | Internal lead pipeline (New → Contacted → Qualified → Follow-up → Converted / Lost) for CarGawy-inventory leads | P0 |
| LEAD-03 | Sellers/dealers see their own inquiries only | P0 |
| LEAD-04 | GA4 / Meta Pixel / Google Ads events via configurable IDs, gated by consent | P1 |

### 4.7 Content
| ID | Requirement | Pri |
|---|---|---|
| CNT-01 | Blog at `/blog/` with categories, author, dates, TOC, FAQ block, related cars | P0 |
| CNT-02 | Static pages (About, Contact, FAQ, Inspection, Legal review, Policies) | P0 |
| CNT-03 | Make/model/location landing pages with unique editorial intro (only when content exists) | P1 |

---

## 5. Non-functional requirements

| Area | Target |
|---|---|
| Performance | LCP < 2.5 s on 4G mid-range Android for home, `/cars/`, car details; listing query < 150 ms server time at 10k listings [R] |
| Mobile | Designed at 360 px first; tested 360, 390, 768, 1024, 1440 |
| Accessibility | WCAG 2.1 AA for contrast, focus, labels; status never conveyed by color alone [C] |
| Languages | Arabic RTL primary, English LTR secondary [C] |
| Security | OWASP Top 10 aware; nonces, capabilities, prepared queries, upload validation [C] |
| Privacy | Personal data minimization, consent, retention, access control [C]; Egypt Personal Data Protection Law No. 151/2020 compliance to be confirmed with counsel **[Q]** |
| Availability | Daily off-site backups, tested restore [C] |

---

## 6. Trust & honesty rules (non-negotiable) [C]

1. Seller-declared facts are **labelled as declarations**, never as verified facts.
2. "Checked by CarGawy" appears only when a recorded inspection is completed, outcome is *Passed* or *Passed with observations*, and it is approved by an authorized manager.
3. Listing approval ≠ inspection. Legal approval ≠ inspection. Neither creates a badge.
4. Every verification panel lists what was **not** checked.
5. No fabricated ratings, reviews, testimonials, KPIs, contact details or policies. Testimonials section stays hidden until real, consented testimonials exist [R].

---

## 7. Out of scope for launch [R]

Online payments, financing calculator with real lender rates, car valuation engine, auctions, native apps, chat (WhatsApp covers it). Each can be added as a module later.

---

## 8. Blog content plan (topics only, not approved facts) [C]

| # | Working title | Category | Search intent | Needs expert verification |
|---|---|---|---|---|
| 1 | دليل شراء سيارة مستعملة في مصر | Buying guides | Informational | Market process |
| 2 | أهم 15 نقطة يجب فحصها قبل شراء سيارة مستعملة | Technical inspection | Informational | Inspector |
| 3 | إزاي تعرف العربية راشة أو فابريكا؟ | Technical inspection | Informational | Inspector |
| 4 | كيفية فحص الشاسيه قبل شراء السيارة | Technical inspection | Informational | Inspector |
| 5 | الأوراق المطلوبة لنقل ملكية سيارة في مصر | Legal advice | Informational/Transactional | **Lawyer — legal claims** |
| 6 | أفضل السيارات الاقتصادية في مصر | Comparisons | Commercial | Current prices |
| 7 | كيف تحدد السعر المناسب لسيارتك المستعملة؟ | Selling | Commercial | Market data |
| 8 | الفرق بين الفتيس الأوتوماتيك وCVT وDCT | Maintenance | Informational | Technical |
| 9 | أهم العلامات التي تكشف مشاكل المحرك | Maintenance | Informational | Inspector |
| 10 | خطوات بيع سيارتك بأمان | Selling | Informational | Legal/process |

Each article links to the relevant service page and to a filtered `/cars/` view (e.g. article 6 → `/cars/?body=hatchback&price_max=…`).

---

## 9. Decision log (major decisions and reasons)

| # | Decision | Reason |
|---|---|---|
| D1 | One account type for buyers **and** individual sellers (`cg_member`); dealers are a separate type [R] | In Egypt the same person buys and sells; forcing a role choice at signup adds friction. Selling capability is part of the member role; abuse is controlled by moderation + listing limits. |
| D2 | Moderation status and availability are separate fields [R] | "Published + Reserved" and "Published + Sold" are both valid. Mixing them into one status (as the brief's list does) makes reporting and SEO handling harder. |
| D3 | "Approved" and "Published" merged for launch; scheduling uses WordPress `future` status [R] | No business need was stated for a gap between approval and publication. |
| D4 | Sellers/dealers use a **front-end** dashboard (`/account/`), never wp-admin [R] | Better mobile UX, branded, smaller attack surface. Staff use wp-admin. |
| D5 | Arabic-first launch; English pages translated progressively [R] **[Q]** | Halves the content workload at launch while the architecture supports both. |
| D6 | Phone number revealed on tap, routed through a tracking redirect [R] | Enables lead tracking without JavaScript and reduces number scraping. |

---

## 10. Assumptions [A]

- A1. Prices are in EGP only; no USD display.
- A2. Listing is free for individuals at launch; dealer monetization comes later.
- A3. Inspections are performed by CarGawy staff (not third-party garages).
- A4. Contact between buyer and seller happens off-platform (WhatsApp/phone); CarGawy is not party to the sale for non-CarGawy listings.
- A5. Hostinger **Business** plan [C] with PHP 8.2+, MariaDB/MySQL, cron, SSH, Git (to verify in hPanel).
- A6. ~~Next.js migration~~ — not needed [C].

---

## 11. Acceptance for this document

Approved when the owner confirms: listing sources table (§3), priorities (§4), trust rules (§6), decisions D1–D6, and answers the blocking questions in §12.

---

## 12. Owner decisions — round 1 [C]

| # | Question | Decision |
|---|---|---|
| Q1 | Official logo | Supplied (circular badge). Stored in `brand/`. |
| Q2 | Digits | **Latin digits**: `750,000 ج.م`, `85,000 كم` |
| Q4 | Next.js project | **Not needed** — no migration |
| Q6 | Inspection pricing | **Never mention inspection price** on the site, in FAQs, emails or schema |
| Q14 | SEO | **Build SEO tools in-house** inside `cargawy-core` (no SEO plugin) |
| Q12 | Hosting | **Hostinger Business** |

## 13. Still open **[Q]**

Not blocking Phase 2 (foundation); needed before the phase shown.

| # | Question | Default if no answer | Needed by |
|---|---|---|---|
| 3 | Launch languages | Arabic first, English added after launch [R] | Phase 6 |
| 5 | Max active listings per individual | 3 | Phase 4 |
| 7 | Inspection validity | 90 days | Phase 8 |
| 8 | Documents required for legal review | — (needs your legal advisor) | Phase 8 |
| 9 | Dealer verification documents | Commercial registry + tax card | Phase 4 |
| 10 | Listing expiry without update | 60 days | Phase 5 |
| 11 | Phone OTP (SMS provider) | Email verification only at launch | Phase 4 |
| 13 | Official phone, WhatsApp, email, address, hours, social links | Placeholders, never invented | Phase 6 |
| 15 | Vector logo (SVG/AI) and a horizontal version without the "فحص معتمد"/stars line | Use the badge as supplied | Phase 6 |
| 16 | Logo claims: the badge contains «فحص معتمد» + five stars — OK to show site-wide? | See DESIGN_SYSTEM §2 | Phase 6 |
