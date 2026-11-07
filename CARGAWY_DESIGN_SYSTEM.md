# CarGawy — Design System

> Blueprint v0.2 — official logo received; digits decision applied. The live demo (`cargawy-demo.html`) implements these tokens so they can be judged on screen.

## 1. Brand foundations [C]

- **Tagline (ar):** عربيتك تستاهل.. اختارها على نور!
- **Tagline (en):** Your Car Deserves the Right Choice.
- **Personality:** trustworthy, professional, modern, transparent, confident, customer-focused, simple.
- **Design idea [R]: "على نور" — clarity as light.** Dark navy surfaces represent the uncertain used-car market; clean white cards and green signal lines represent information made visible. Green is used where something is *verified or actionable*, never as decoration. This gives the palette a reason, not just a look.

## 2. Logo [C]

**Supplied:** circular badge, 1254×1254 raster (`brand/cargawy-logo-original.webp`). Contents: green ring, white-and-green car outline, "CarGawy" wordmark (Car white, Gawy green), «كار جاوي», green shield with check, «فحص معتمد» pill, five gold stars, tyre-tread texture, on a navy-to-blue background. Its greens and navy match the palette.

**Usage rules**
- Never redraw, recolour, crop or stretch the badge; always circular with its own background.
- Header: 56 px desktop / 48 px mobile. Footer: 120 px. Favicon/app icon: badge at 512 px.
- Clear space around the circle: 1/8 of its diameter.
- Because the badge carries its own dark background, it sits well on both the white header and the navy footer.

**Findings that need your decision**
1. **Legibility at header size.** At 48–56 px the "CarGawy" wordmark inside the circle is hard to read and «كار جاوي», «فحص معتمد» and the stars disappear (visible in the demo on mobile). **[R]** Ask your designer for a **horizontal lockup** (car outline + CarGawy + كار جاوي, no badge line) for the header, keeping the circle for footer, social and favicon.
2. **Trust claims inside the logo.** «فحص معتمد» and five stars appear on every page, including next to cars that are *not* inspected and with no real rating behind the stars. That conflicts with the honesty rules (PRD §6). **[R]** Use the version without that line on the website; keep the full badge for social media. **[Q]**
3. **Vector file.** The supplied file is raster. **[R]** Request SVG/AI for sharp printing, signage and large sizes.

## 3. Color tokens

| Token | Hex | Role |
|---|---|---|
| `--cg-navy-900` | `#061629` Deep Navy | Primary brand surface: hero, footer, dashboard sidebar, primary text on light |
| `--cg-navy-700` | `#142B35` Dark Blue | Secondary dark surfaces, nav, dark cards |
| `--cg-green-600` | `#22A66F` Fresh Green | Primary action (buttons, links), verified states |
| `--cg-green-400` | `#00E65A` Bright Green | **Sparingly:** focus ring on dark, active filter dot, selected state on dark only |
| `--cg-paper` | `#F2F4F2` Off White | Page background |
| `--cg-white` | `#FFFFFF` | Cards, inputs, text on dark |
| `--cg-gold` | `#D6A85F` | Premium only: "Featured" and "CarGawy inventory" marks |

Derived (not new brand colors, needed for UI) [R]:

| Token | Value | Use |
|---|---|---|
| `--cg-ink` | `#061629` | Body text (navy, not black) |
| `--cg-ink-muted` | `#4A5A63` | Secondary text (7.0:1 on white) |
| `--cg-line` | `#DCE2DF` | Borders, dividers |
| `--cg-green-700` | `#17794F` | Green text on white, white-text buttons (5.4:1 on white — AA) |
| `--cg-warn` | `#B26A00` + `#FFF4E0` | Observations, pending |
| `--cg-danger` | `#B42318` + `#FDECEA` | Rejected, failed, issues |
| `--cg-info` | `#1F5E8C` + `#E8F1F8` | Neutral info, "seller declaration" |

### Contrast checks (WCAG AA)
| Pair | Ratio | Result |
|---|---|---|
| White on `#061629` | 18.2:1 | AAA |
| White on `#142B35` | 14.7:1 | AAA |
| White on `#22A66F` | 3.1:1 | Fails AA for normal text. **Rule [R]: primary button = navy text on Fresh Green (5.8:1, AA)**, or white text on `#17794F` (5.4:1). Demo uses navy-on-green. |
| `#22A66F` on `#061629` | 5.8:1 | AA — green links/labels on dark OK |
| `#00E65A` on `#061629` | 10.8:1 | AAA — but reserved for accents |
| `#00E65A` on white | 1.6:1 | **Never** for text on light |
| `#D6A85F` on `#061629` | 8.3:1 | AA — gold badges on dark |
| `#D6A85F` on white | 2.1:1 | **Never** for text on light; on light use navy text inside a gold-tinted chip |

### Status semantics (always icon + text, never color alone) [C]
| State | Chip |
|---|---|
| Checked by CarGawy | green fill, ✓ shield icon, "تم الفحص بواسطة CarGawy" |
| Inspection: passed with observations | green outline + amber dot, text |
| Inspection: requested/scheduled | info chip, clock icon |
| Not inspected | neutral grey outline, "لم يتم الفحص بعد" |
| Documents reviewed | info chip, document icon (never green shield — so it can't be confused with inspection) |
| Seller declaration | info text label "إقرار البائع" |
| Pending / Rejected / Sold / Reserved | warn / danger / navy / gold |

## 4. Typography [C]

| Role | Arabic | English | Notes |
|---|---|---|---|
| Display & headings | **Cairo** 700/800 | Inter 700/800 | |
| Body & UI | **Cairo** 400/600 | Inter 400/600 | Cairo chosen over Tajawal [R]: better weight range and number legibility at small sizes |
| Data (prices, km) | Cairo 700 with `tabular-nums` | Inter 600 `tabular-nums` | |

Self-host the fonts in `cargawy-theme/assets/fonts/` (WOFF2, subset Arabic + Latin) for speed and privacy [R]; the demo uses Google Fonts only for convenience.

**Scale (rem, base 16px):** 0.75 · 0.875 · 1 · 1.125 · 1.25 · 1.5 · 1.875 · 2.25 · 3 (hero desktop only). Mobile hero H1 caps at 1.875rem.
**Line height:** Arabic body 1.8, Arabic headings 1.35; English body 1.6, headings 1.2.
**Numbers [C]:** Latin (Western) digits everywhere, Arabic currency label — `750,000 ج.م`. Thousands separator `,`.
**Mileage:** `85,000 كم`. **Year:** plain digits, no grouping.

## 5. Spacing, radius, shadows, breakpoints

- **Spacing scale (px):** 4 · 8 · 12 · 16 · 24 · 32 · 48 · 64 · 96.
- **Radius:** 6 (inputs, chips) · 12 (cards) · 999 (pills). Not one radius everywhere.
- **Shadows:** `sh-1: 0 1px 2px rgba(6,22,41,.06), 0 1px 1px rgba(6,22,41,.04)` (cards at rest) · `sh-2: 0 8px 24px rgba(6,22,41,.12)` (hover, sheets, sticky bar).
- **Breakpoints:** `sm 480` · `md 768` · `lg 1024` · `xl 1280`. Container max 1200px; gutter 16px mobile, 24px ≥ md.
- **Motion:** 150–200 ms ease-out for hover/press; sheets 250 ms; all disabled under `prefers-reduced-motion`.

## 6. RTL / LTR rules

- `<html dir="rtl" lang="ar">` / `dir="ltr" lang="en"`; CSS uses **logical properties only** (`margin-inline-start`, `inset-inline-end`, `padding-inline`) so one stylesheet serves both.
- Directional icons (arrows, chevrons, "next") mirror in RTL; brand logos, car photos, media controls, phone numbers and prices do not.
- Phone numbers and plate/VIN fragments wrapped in `<bdi dir="ltr">`.
- Mixed text (e.g. "Hyundai Elantra 2019" inside Arabic) wrapped in `<bdi>` to avoid punctuation jumping.

## 7. Component library

| Component | Key specs |
|---|---|
| **Button** | Sizes S 36 / M 44 / L 52 px height (min tap target 44). Variants: Primary (green, navy text), Secondary (navy), Outline, Ghost, WhatsApp (green with WA icon), Danger. States: hover, focus (2px `--cg-green-400` ring with offset on dark, navy ring on light), disabled, loading (spinner, label kept). |
| **Header** | 64 px mobile / 72 px desktop, white, sticky; navy on hero overlay variant. |
| **Mobile nav** | Full-height sheet from inline-end, focus-trapped, Esc closes. |
| **Footer** | Navy-900, 4 columns → accordion on mobile. |
| **Search panel** | White card over hero, 6 fields desktop (grid), 2 + "more" on mobile. |
| **Filter sidebar / sheet** | Groups collapsible; range inputs as two number fields + preset chips; "Show N cars" sticky apply button on mobile. |
| **Car card** | 4:3 photo, source chip (top inline-start), favorite button (top inline-end), title (make model trim), year · km · transmission row, location, price (largest text on card), verification line at bottom. Whole card is one link; favorite is a separate button. |
| **Featured car card** | Same + gold "مميزة" chip; never a different layout (consistency in grids). |
| **Badges** | Price, Seller type (Individual / Dealer / CarGawy), Inspection, Legal, Listing status — see §3 semantics. |
| **Spec icon row** | Icon + value, 4 items max on cards. |
| **Gallery** | Main image + thumb strip; swipe; counter "3 / 12"; full-screen viewer. |
| **Breadcrumbs, Pagination** | Pagination shows numbered links; RTL order mirrored. |
| **Modal / Sheet / Confirm dialog** | Focus trap, labelled title, destructive confirm uses danger button + explicit verb ("رفض الإعلان"). |
| **Form fields** | Label above, help text below, error text below in danger with icon; `inputmode="numeric"` for price/km; Egyptian mobile pattern `01[0125]xxxxxxxx`. |
| **Alerts, Empty, Loading (skeleton), Error states** | Every list has all four designed. |
| **Dashboard sidebar** | Navy-900, 240px, collapsible to icons; on mobile becomes bottom tab bar (5 items). |
| **Dashboard table** | Sticky header, row actions, status chips, responsive → stacked cards < 768. |
| **Stat card** | Label, value (tabular), period, delta only when a comparison period exists. |

## 8. Imagery

- Real car photos only (no stock cars presented as listings). Hero needs an owner-supplied or licensed photo **[Q]**.
- Listing photo guide for sellers: 8 required angles (front ¾, rear ¾, side, interior front, dashboard/odometer, rear seats, engine bay, tires).
- Server generates sizes: 480, 768, 1200, 1600 wide, WebP, 4:3 crop for cards.
- Placeholder when a listing has no photo: navy silhouette illustration (as in the demo).

## 9. Voice & copy

Egyptian-friendly Modern Standard Arabic with light colloquial touches in marketing headlines (matching the tagline), plain MSA in policies and forms. Direct verbs on buttons: «تصفح العربيات»، «بيع عربيتك»، «كلّم البائع». Error messages say what happened and how to fix it.
