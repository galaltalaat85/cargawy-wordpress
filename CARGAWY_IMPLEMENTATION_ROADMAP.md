# CarGawy — Implementation Roadmap

> Blueprint v0.2 — round 1 decisions applied. Each phase ends with a demo on staging and **your sign-off** before the next phase starts. Durations are rough estimates for one developer team working with Claude Code [A].

## Plugin & theme structure (final proposal)

```
cargawy-core/
├── cargawy-core.php            # header, constants, autoloader, boot
├── uninstall.php               # deletes data only if setting enabled
├── composer.json               # PSR-4 autoload (CarGawy\Core\)
├── includes/
│   ├── Plugin.php              # service container / module loader
│   ├── Activation/             # Installer, Migrations/0001_initial.php …
│   ├── Database/               # Tables, Repositories
│   ├── PostTypes/  Taxonomies/ # Car, Dealer, statuses, terms + seeders (makes, governorates)
│   ├── Fields/                 # VehicleSchema (labels, validation, i18n)
│   ├── Permissions/            # Roles, Capabilities, MetaCaps
│   ├── Workflow/               # StateMachine base + Listing, Inspection, LegalReview, Dealer, Lead
│   ├── Marketplace/            # Search, Sorting, Similar
│   ├── Dealers/  Inventory/  Moderation/  Inspection/  LegalReview/
│   ├── Leads/  Favorites/  Analytics/  Audit/  Files/
│   ├── Api/                    # REST controllers
│   ├── Admin/                  # Menu, ListTables, Screens, Settings
│   ├── Frontend/               # AccountRouter, FormHandlers, TemplateLoader, TrackingRedirects
│   └── Seo/                    # Schema, robots rules, SEO-plugin adapters
├── templates/                  # account/*, forms/*, emails/* (theme can override in /cargawy/)
├── assets/                     # admin + account JS/CSS (built)
├── languages/                  # cargawy-core.pot, ar.po/.mo
└── tests/                      # PHPUnit (wp-phpunit) + fixtures

cargawy-theme/
├── style.css  functions.php  theme.json  screenshot.png
├── inc/                        # setup, assets, template-tags, blocks registration
├── assets/{css,js,images,fonts}/
├── blocks/                     # hero-search, featured-cars, faq, cta (block.json each)
├── template-parts/{header,footer,homepage,cars,blog,components}/
├── page-templates/             # sell, dealer-registration, service pages
├── single-cg_car.php  archive-cg_car.php  single-cg_dealer.php  archive-cg_dealer.php
├── home.php  single.php  page.php  404.php  search.php
├── cargawy/                    # overrides of plugin templates (optional)
├── languages/
└── README.md
```

Changes vs. the brief's structure (with reasons): PSR-4 class folders instead of flat function folders (testability); a shared `Workflow/` for all state machines (one place for permission + audit); `Seo/` added; `blocks/` added for editor-managed homepage sections; `single-/archive-` templates at theme root because that is where WordPress looks for them.

## Phases

| # | Phase | Scope | DB changes | Acceptance (summary) | Est. |
|---|---|---|---|---|---|
| 1 | Discovery & architecture | This blueprint, demo, answers to open questions, logo, hosting check | — | You approve the 8 documents | **now** |
| 2 | Foundation | Repo, CI (PHPCS/WPCS, PHPUnit), local env, staging on Hostinger, plugin boot, migrations, roles & caps, audit log, settings page | All tables created empty | Activation/deactivation clean; roles exist; CI green | 1 wk |
| 3 | Vehicles & inventory | `cg_car`, taxonomies + seeders (makes/models, 27 governorates), VehicleSchema, admin edit screen, media ordering, inventory staff flow | `cg_vehicles` filled | Staff add/edit/publish inventory car; data validated | 1.5 wk |
| 4 | Accounts, sellers, dealers | Register/login/reset, `/account/` router, add-car wizard, dealer registration & verification, ownership caps | dealer members | Member submits → pending; cannot touch others' cars | 2 wk |
| 5 | Moderation | Pending queue, approve/reject with reasons, suspend, expiry cron, emails | moderation | Full lifecycle tests pass | 1 wk |
| 6 | Public theme | Design tokens, components, home, `/cars/` search, car details, dealers, service & static pages, RTL/LTR | — | Visual review vs. approved demo at 360/768/1440; Lighthouse ≥ 90 mobile perf on staging | 2.5 wk |
| 7 | Dashboards | Member & dealer dashboards, internal overview KPIs | stats_daily | Each KPI traced to a query | 1.5 wk |
| 8 | Verification | Inspection & legal workflows, booking/scheduling (no price shown), private files, badge logic, public panels | inspections, legal, files | All verification tests (QA §3) pass | 2 wk |
| 9 | Blog & SEO (in-house) | Blog templates, TOC, related cars; SEO module: meta fields, canonical, robots, sitemaps, OG, JSON-LD, breadcrumbs, redirects, 404 monitor, editor checks | redirects, 404_log | Rich Results test clean; sitemaps validate in Search Console; slug change creates 301; noindex rules verified | 2 wk |
| 10 | Leads & analytics | Tracking redirects, lead pipeline, GA4/Meta/Ads with consent | leads, events | Events visible in GA4 DebugView only after consent | 1 wk |
| 11 | Hardening & QA | Security review, load test 10k fake listings on staging, accessibility audit, backup/restore drill | — | QA strategy exit criteria met | 1.5 wk |
| 12 | Launch | Content entry, DNS/SSL, production deploy, Search Console, monitoring | — | Launch checklist signed | 0.5 wk |

**Each phase delivers:** scope note · files changed · migrations · dependencies · security notes · tests run with results · updated docs · staging URL.

## Dependencies on you (needed before the phase starts)

| Needed | By phase |
|---|---|
| ~~Logo, digits, Next.js, SEO approach, hosting plan~~ — received | — |
| Hostinger Business hPanel access (to create staging) | 2 |
| Listing limits, expiry, photo minimum | 4–5 |
| Inspection policy, checklist, validity | 8 |
| Legal document list + disclaimer text from counsel | 8 |
| Policies (privacy, terms, cookies, listing rules) from counsel | 11 |
| Real contact details, hero photo, about-us content, horizontal/SVG logo | 6 / 12 |
| Analytics IDs | 10 |
