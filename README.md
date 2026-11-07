# CarGawy — WordPress marketplace

Used-car marketplace for Egypt. «عربيتك تستاهل.. اختارها على نور!»

| Part | Path | Holds |
|---|---|---|
| `cargawy-core` plugin | `wp-content/plugins/cargawy-core/` | All business logic: data model, roles, workflows, audit log, settings, SEO (later phases) |
| `cargawy-theme` | `wp-content/themes/cargawy-theme/` | Presentation only — starts in Phase 6 |
| Blueprint | `docs/blueprint/` | Approved product, architecture, schema, roles, sitemap, design system, roadmap, QA |
| Design demo | `docs/design-demo.html` | Clickable prototype used to approve the look and flows |

WordPress core, uploads and third-party plugins are **not** committed. Production needs **no Composer**: the plugin has its own autoloader. Composer is for development tools only.

## Status

**Phase 2 — Foundation: done.** See [`docs/PHASE-2-REPORT.md`](docs/PHASE-2-REPORT.md).

## Local development

Requirements: PHP 8.1+, Composer, MySQL 8 or MariaDB 10.6+.

```bash
composer install
```

Create the databases once (adjust user/password):

```bash
mysql -e "CREATE DATABASE cargawy_dev; CREATE DATABASE cargawy_test; CREATE USER 'cg'@'localhost' IDENTIFIED BY 'cg_local_pw'; GRANT ALL ON cargawy_dev.* TO 'cg'@'localhost'; GRANT ALL ON cargawy_test.* TO 'cg'@'localhost';"
```

Throwaway local site with the plugin linked in:

```bash
bin/setup-local.sh
```

```bash
php -S localhost:8080 -t .local/site
```

## Quality checks (same as CI)

```bash
composer lint
```

```bash
composer test
```

Test database settings come from `CG_TEST_DB_NAME`, `CG_TEST_DB_USER`, `CG_TEST_DB_PASSWORD`, `CG_TEST_DB_HOST` (defaults match the local commands above).

## Conventions

- Namespace `CarGawy\Core`, PSR-4 under `includes/`. WordPress Coding Standards (WPCS 3) enforced in CI.
- Global helpers: `cargawy_audit()`, `cargawy_setting()`. Tables and capabilities use the `cg_` prefix.
- Every status change goes through a `Workflow\StateMachine` subclass: transition table → capability check → guard → save → audit row → `cargawy/{entity}/transition` action.
- Roles and capabilities are defined in code (`Permissions/Capabilities.php`) and versioned. Bump `Roles::VERSION` after changing the matrix.
- Schema changes: edit `Database/Schema.php`, bump `Installer::DB_VERSION`, add a data migration in `Installer::run_migrations()` if needed.
- Deactivation never deletes data. Uninstall deletes data only if *Delete all CarGawy data when the plugin is deleted* is enabled in CarGawy → Settings.
