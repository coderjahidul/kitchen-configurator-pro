# Phase 2 — Plugin Bootstrap, Composer, Container, Migrations

## Deliverables

| File | Purpose |
|------|---------|
| `kitchen-configurator-pro.php` | Plugin entry point, constants, autoload, hooks |
| `composer.json` | PSR-4 autoloading configuration |
| `.gitignore` | Ignore vendor, node_modules, etc. |
| `uninstall.php` | Drop tables, delete options, remove capabilities |
| `src/Plugin.php` | Singleton orchestrator, boot, auto-upgrade |
| `src/Container.php` | Lightweight DI container |
| `src/Activator.php` | Activation: migrations, caps, defaults |
| `src/Deactivator.php` | Deactivation: flush rewrite rules |
| `src/Contracts/MigrationInterface.php` | Migration contract |
| `src/Database/AbstractMigration.php` | Shared migration helpers |
| `src/Database/MigrationRunner.php` | Version tracking and execution |
| `src/Database/Migrator.php` | Public migration facade |
| `src/Database/Migrations/Migration_1_0_0.php` | Initial schema (11 tables) |
| `src/Support/Helpers.php` | Table names, migration registry |
| `src/Security/CapabilityManager.php` | `manage_kcp` capability |

## Installation

```bash
cd wp-content/plugins/kitchen-configurator-pro
composer install --no-dev
```

Activate the plugin in WordPress admin. Tables are created automatically.

## Architecture Decisions

### Bootstrap flow

1. Define constants (`KCP_VERSION`, `KCP_DB_VERSION`, paths)
2. Load Composer autoloader (admin notice if missing)
3. Declare WooCommerce HPOS compatibility
4. Register activation/deactivation hooks
5. Boot `Plugin::instance()` → register container → hooks

### Auto-upgrade

On `plugins_loaded`, if `kcp_db_version` < `KCP_DB_VERSION`, `Migrator::run()` executes pending migrations. Same logic runs on activation.

### Container

Minimal DI without external packages:

- `bind()` — new instance every resolve
- `singleton()` — cached instance
- `get()` / `has()` / `forget()`

Service providers (Phase 3+) will register bindings in `Plugin::boot()`.

### Migration system

- `MigrationInterface` — `up()`, `down()`, `version()`
- `MigrationRunner` — tracks executed versions in `kcp_migrations`
- `Helpers::migration_classes()` — central registry (add new classes here)
- `Migration_1_0_0` — creates all catalog + configuration tables from Phase 1 schema

### Uninstall safety

Filter `kcp_uninstall_drop_tables` (default `true`) allows preserving data on uninstall.

## Verification

After activation, confirm in phpMyAdmin or WP-CLI:

```bash
wp db query "SHOW TABLES LIKE '%kcp_%'"
wp option get kcp_db_version
```

Expected: `1.0.0` and 11 `kcp_*` tables (+ `kcp_migrations`).
