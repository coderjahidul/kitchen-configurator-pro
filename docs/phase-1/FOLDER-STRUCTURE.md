# Kitchen Configurator Pro — Folder Structure

## Complete Directory Tree

Legend: **✅** implemented · **⏳** planned (future phase)

```
kitchen-configurator-pro/
│
├── kitchen-configurator-pro.php          # ✅ Plugin bootstrap (main file)
├── composer.json                         # ✅ PSR-4 autoloading + dependencies
├── composer.lock                         # ✅
├── uninstall.php                         # ✅ Cleanup on plugin deletion
├── readme.txt                            # ⏳ WordPress.org readme (optional)
├── README.md                             # ✅ Project readme
├── .gitignore                            # ✅
├── phpcs.xml                             # ⏳ WordPress Coding Standards (Phase 9)
├── phpunit.xml.dist                      # ⏳ PHPUnit config (Phase 9)
│
├── docs/
│   ├── phase-1/                          # ✅
│   │   ├── PHASE-1.md
│   │   ├── ARCHITECTURE.md
│   │   ├── FOLDER-STRUCTURE.md
│   │   ├── DATABASE-SCHEMA.md
│   │   ├── ER-DIAGRAM.md
│   │   └── CLASS-DIAGRAM.md
│   ├── phase-2/                          # ✅
│   │   └── PHASE-2.md
│   ├── phase-3/                          # ✅
│   │   └── PHASE-3.md
│   └── api/                              # ⏳ OpenAPI spec (Phase 5)
│
├── database/
│   └── schema/
│       └── schema.sql                    # ✅ Reference DDL (Phase 1)
│
├── languages/                            # ⏳
│   └── kitchen-configurator-pro.pot      # i18n template
│
├── assets/
│   ├── admin/                            # ✅
│   │   ├── css/
│   │   │   └── admin.css
│   │   └── js/
│   │       └── admin.js
│   ├── frontend/                         # ⏳ Phase 6
│   │   ├── css/
│   │   │   └── configurator.css
│   │   └── js/
│   │       └── configurator.js
│   └── images/                           # ⏳
│       └── placeholder-cabinet.svg
│
├── src/                                  # PSR-4: KitchenConfiguratorPro\
│   │
│   ├── Plugin.php                        # ✅ Main plugin orchestrator
│   ├── Container.php                     # ✅ Service container (DI)
│   ├── Activator.php                     # ✅ Activation hooks
│   ├── Deactivator.php                   # ✅ Deactivation hooks
│   │
│   ├── Admin/                            # ✅ Phase 3
│   │   ├── AdminServiceProvider.php
│   │   ├── AbstractCrudPage.php
│   │   ├── Menu.php
│   │   ├── Assets.php
│   │   └── Pages/
│   │       ├── DashboardPage.php
│   │       ├── LayoutsPage.php
│   │       ├── CabinetCategoriesPage.php
│   │       ├── CabinetsPage.php
│   │       ├── MaterialsPage.php
│   │       ├── ColorsPage.php
│   │       ├── HandlesPage.php
│   │       ├── AccessoriesPage.php
│   │       ├── PricingRulesPage.php
│   │       ├── ConfigurationsPage.php
│   │       └── SettingsPage.php
│   │
│   ├── Api/                              # ⏳ Phase 5
│   │   ├── ApiServiceProvider.php
│   │   ├── RestController.php            # Base REST controller
│   │   └── Controllers/
│   │       ├── CatalogController.php
│   │       ├── ConfigurationController.php
│   │       ├── PricingController.php
│   │       ├── ProjectController.php
│   │       ├── CartController.php
│   │       └── QuoteController.php
│   │
│   ├── Contracts/
│   │   ├── RepositoryInterface.php       # ✅
│   │   ├── PricingCalculatorInterface.php  # ⏳ Phase 4
│   │   ├── MigrationInterface.php        # ✅
│   │   ├── PdfGeneratorInterface.php     # ⏳ Phase 7
│   │   └── CacheInterface.php            # ⏳ Phase 5
│   │
│   ├── Database/                         # ✅ Phase 2
│   │   ├── MigrationRunner.php
│   │   ├── Migrator.php
│   │   ├── AbstractMigration.php
│   │   └── Migrations/
│   │       ├── Migration_1_0_0.php         # Initial schema
│   │       └── Migration_1_1_0.php       # Future migrations
│   │
│   ├── Domain/
│   │   ├── Entities/                     # ✅ Phase 3 (Project ⏳ Phase 5)
│   │   │   ├── Layout.php
│   │   │   ├── CabinetCategory.php
│   │   │   ├── Cabinet.php
│   │   │   ├── Material.php
│   │   │   ├── Color.php
│   │   │   ├── Handle.php
│   │   │   ├── Accessory.php
│   │   │   ├── PricingRule.php
│   │   │   ├── Configuration.php
│   │   │   └── Project.php               # ⏳
│   │   ├── ValueObjects/
│   │   │   ├── Dimensions.php
│   │   │   ├── Money.php
│   │   │   ├── Uuid.php
│   │   │   ├── PriceHash.php
│   │   │   └── Position.php
│   │   ├── DTO/
│   │   │   ├── ConfigurationInput.php
│   │   │   ├── PricingSnapshot.php
│   │   │   ├── LineItem.php
│   │   │   └── CatalogResponse.php
│   │   ├── Enums/
│   │   │   ├── ConfigurationStatus.php
│   │   │   ├── MaterialType.php
│   │   │   ├── PricingRuleType.php
│   │   │   └── CalculationType.php
│   │   └── Exceptions/
│   │       ├── KcpException.php
│   │       ├── ValidationException.php
│   │       ├── PricingException.php
│   │       └── NotFoundException.php
│   │
│   ├── Frontend/
│   │   ├── FrontendServiceProvider.php
│   │   ├── Shortcode.php                 # [kitchen_configurator]
│   │   └── Assets.php
│   │
│   ├── Integration/
│   │   └── WooCommerce/
│   │       ├── WooCommerceServiceProvider.php
│   │       ├── ProductManager.php        # Container product setup
│   │       ├── CartHandler.php
│   │       ├── CheckoutHandler.php
│   │       ├── OrderHandler.php
│   │       └── OrderMetaDisplay.php
│   │
│   ├── Repositories/                     # ✅ Phase 3 (history/project ⏳)
│   │   ├── AbstractRepository.php
│   │   ├── LayoutRepository.php
│   │   ├── CabinetCategoryRepository.php
│   │   ├── CabinetRepository.php
│   │   ├── MaterialRepository.php
│   │   ├── ColorRepository.php
│   │   ├── HandleRepository.php
│   │   ├── AccessoryRepository.php
│   │   ├── PricingRuleRepository.php
│   │   ├── ConfigurationRepository.php
│   │   ├── ProjectRepository.php           # ⏳
│   │   └── ConfigurationHistoryRepository.php  # ⏳
│   │
│   ├── Services/
│   │   ├── CatalogService.php
│   │   ├── ConfigurationService.php
│   │   ├── ProjectService.php
│   │   ├── ValidationService.php
│   │   ├── CacheService.php
│   │   ├── QuoteService.php
│   │   └── Pricing/
│   │       ├── PricingEngine.php
│   │       ├── PriceHashGenerator.php
│   │       └── Calculators/
│   │           ├── BasePriceCalculator.php
│   │           ├── DimensionCalculator.php
│   │           ├── MaterialCalculator.php
│   │           ├── HandleCalculator.php
│   │           ├── AccessoryCalculator.php
│   │           └── RuleEngineCalculator.php
│   │
│   ├── Security/
│   │   ├── CapabilityManager.php         # ✅
│   │   ├── NonceManager.php              # ⏳ Phase 8
│   │   └── RateLimiter.php               # ⏳ Phase 8
│   │
│   └── Support/
│       ├── Helpers.php                   # ✅
│       ├── Arr.php                       # ✅
│       └── Json.php                      # ⏳
│
├── templates/
│   ├── admin/                            # ✅ Phase 3
│   │   ├── dashboard.php
│   │   ├── crud-list.php
│   │   ├── crud-form.php
│   │   ├── configurations-list.php
│   │   ├── configuration-view.php
│   │   ├── settings.php
│   │   └── partials/
│   │       └── admin-notice.php
│   ├── frontend/
│   │   └── configurator.php              # Shortcode template shell
│   └── pdf/
│       └── quote-template.php
│
├── frontend-src/                         # Vite source (Phase 6)
│   ├── package.json
│   ├── vite.config.js
│   └── src/
│       ├── main.js
│       ├── api/
│       │   └── client.js
│       ├── components/
│       │   ├── LayoutSelector.js
│       │   ├── CabinetCanvas.js
│       │   ├── DimensionPanel.js
│       │   ├── MaterialPicker.js
│       │   ├── ColorPicker.js
│       │   ├── HandlePicker.js
│       │   ├── AccessoryPicker.js
│       │   ├── PriceSummary.js
│       │   └── ProjectHistory.js
│       ├── state/
│       │   └── store.js
│       └── utils/
│           └── format.js
│
└── tests/                                # Phase 9
    ├── bootstrap.php
    ├── Unit/
    │   ├── Pricing/
    │   │   └── PricingEngineTest.php
    │   └── Services/
    │       └── ConfigurationServiceTest.php
    └── Integration/
        └── Api/
            └── ConfigurationControllerTest.php
```

---

## Namespace Mapping (PSR-4)

| Path | Namespace |
|------|-----------|
| `src/` | `KitchenConfiguratorPro\` |
| `src/Admin/` | `KitchenConfiguratorPro\Admin\` |
| `src/Api/` | `KitchenConfiguratorPro\Api\` |
| `src/Domain/` | `KitchenConfiguratorPro\Domain\` |
| `src/Repositories/` | `KitchenConfiguratorPro\Repositories\` |
| `src/Services/` | `KitchenConfiguratorPro\Services\` |
| `src/Integration/WooCommerce/` | `KitchenConfiguratorPro\Integration\WooCommerce\` |

**composer.json autoload:**

```json
{
  "autoload": {
    "psr-4": {
      "KitchenConfiguratorPro\\": "src/"
    }
  }
}
```

---

## Layer Responsibilities

| Layer | Directory | Responsibility |
|-------|-----------|----------------|
| Bootstrap | Root PHP files | WP plugin header, activation, autoload |
| Orchestration | `src/Plugin.php`, `*ServiceProvider.php` | Wire dependencies, register hooks |
| Presentation | `src/Admin/`, `src/Api/`, `src/Frontend/`, `templates/` | HTTP/UI boundaries |
| Application | `src/Services/` | Business logic, orchestration |
| Domain | `src/Domain/` | Pure business objects, no WP deps |
| Infrastructure | `src/Repositories/`, `src/Database/`, `src/Integration/` | Persistence, external systems |
| Assets | `assets/`, `frontend-src/` | Static files and build source |

---

## File Naming Conventions

- **Classes:** `PascalCase.php` matching class name
- **Interfaces:** `*Interface.php` in `Contracts/`
- **Migrations:** `Migration_X_Y_Z.php` (version-based)
- **Templates:** `kebab-case.php`
- **DB tables:** `{$wpdb->prefix}kcp_*` (e.g., `wp_kcp_cabinets`)
- **REST namespace:** `kcp/v1`
- **Options:** `kcp_*` (e.g., `kcp_db_version`, `kcp_wc_product_id`)

---

## WordPress Plugin Header (Reference)

```php
/**
 * Plugin Name:       Kitchen Configurator Pro
 * Plugin URI:        https://example.com/kitchen-configurator-pro
 * Description:       Production kitchen cabinet configurator with WooCommerce integration.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Your Company
 * Text Domain:       kitchen-configurator-pro
 * Domain Path:       /languages
 * WC requires at least: 9.0
 * WC tested up to:   9.5
 */
```

---

*End of Folder Structure Document*
