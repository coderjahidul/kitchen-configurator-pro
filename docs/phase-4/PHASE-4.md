# Phase 4 — Pricing Engine

> **Status:** ✅ Complete  
> **DB Version:** 1.1.0

---

## Deliverables

### Migration 1.1.0 — Worktop & Plinth catalog entities

| File | Purpose |
|------|---------|
| `src/Database/Migrations/Migration_1_1_0.php` | Creates `kcp_worktops` and `kcp_plinths` |
| `src/Domain/Entities/Worktop.php` | Worktop entity |
| `src/Domain/Entities/Plinth.php` | Plinth entity |
| `src/Repositories/WorktopRepository.php` | Worktop CRUD |
| `src/Repositories/PlinthRepository.php` | Plinth CRUD |
| `src/Admin/Pages/WorktopsPage.php` | Admin CRUD |
| `src/Admin/Pages/PlinthsPage.php` | Admin CRUD |

### Domain layer

| File | Purpose |
|------|---------|
| `src/Domain/ValueObjects/Money.php` | Decimal-safe currency amounts |
| `src/Domain/ValueObjects/Dimensions.php` | Width/height/depth (mm) |
| `src/Domain/ValueObjects/PriceHash.php` | SHA-256 integrity hash |
| `src/Domain/DTO/ConfigurationInput.php` | Pricing input DTO |
| `src/Domain/DTO/LineItem.php` | Pricing line item |
| `src/Domain/DTO/PricingSnapshot.php` | Pricing result DTO |
| `src/Domain/Exceptions/KcpException.php` | Base exception |
| `src/Domain/Exceptions/ValidationException.php` | Validation errors |
| `src/Domain/Exceptions/PricingException.php` | Pricing errors |
| `src/Domain/Exceptions/NotFoundException.php` | Not found |
| `src/Contracts/PricingCalculatorInterface.php` | Calculator contract |

### Services

| File | Purpose |
|------|---------|
| `src/Services/ValidationService.php` | Configuration + dimension validation |
| `src/Services/Pricing/PricingEngine.php` | Main orchestrator |
| `src/Services/Pricing/PriceHashGenerator.php` | Hash generate/verify |
| `src/Services/Pricing/CatalogContextBuilder.php` | Resolve catalog entities |
| `src/Services/Pricing/CalculationContext.php` | Mutable pipeline state |
| `src/Services/Pricing/ConditionEvaluator.php` | Rule condition evaluation |
| `src/Services/Pricing/AbstractCalculator.php` | Calculator helpers |

### Calculators (priority order)

| Calculator | Priority | Scope |
|------------|----------|-------|
| `BasePriceCalculator` | 10 | Per-cabinet `base_price` |
| `DimensionCalculator` | 20 | `dimension_price_json` surcharges |
| `MaterialCalculator` | 30 | Material/color modifiers per cabinet |
| `HandleCalculator` | 40 | Handle price per cabinet |
| `AccessoryCalculator` | 50 | Per-cabinet + per-kitchen accessories |
| `WorktopCalculator` | 60 | Worktop + finish material/color |
| `PlinthCalculator` | 70 | Plinth run length pricing |
| `RuleEngineCalculator` | 100 | `kcp_pricing_rules` custom rules |

### Infrastructure

| File | Purpose |
|------|---------|
| `src/CoreServiceProvider.php` | Registers repos + pricing services globally |
| `src/Plugin.php` | Boots `CoreServiceProvider` on every request |

---

## Architecture

### Pipeline

```
ConfigurationInput
    → ValidationService
    → CatalogContextBuilder (load entities)
    → Calculators (sorted by priority)
    → VAT from kcp_settings.vat_rate
    → PricingSnapshot + price_hash
```

### Updated configuration JSON (`global_options`)

```json
{
  "worktop_id": 1,
  "worktop_material_id": 2,
  "worktop_color_id": 9,
  "worktop_length": 3000,
  "worktop_depth": 600,
  "plinth_id": 2,
  "plinth_length": 5000,
  "plinth_height": 150,
  "accessories": [201]
}
```

### Usage (PHP)

```php
$engine = kcp_plugin()->container()->get( \KitchenConfiguratorPro\Services\Pricing\PricingEngine::class );

$snapshot = $engine->calculate_from_array( array(
    'layout_id' => 1,
    'title'     => 'My Kitchen',
    'cabinets'  => array( /* ... */ ),
    'global_options' => array( /* ... */ ),
) );

// $snapshot->to_array() — includes line_items, subtotal, tax, total, price_hash
```

### Price hash

Canonical JSON of snapshot (excluding `price_hash`) is hashed with SHA-256. Used in Phase 7 for cart/checkout integrity validation.

---

## Admin

New menu items: **Worktops** (`kcp-worktops`), **Plinths** (`kcp-plinths`)

Settings: **Display VAT Rate (%)** — optional snapshot VAT (WooCommerce handles checkout tax).

---

## Verification

After plugin load / re-activation:

```bash
wp option get kcp_db_version          # Expected: 1.1.0
wp db query "SHOW TABLES LIKE '%kcp_worktop%'"
wp db query "SHOW TABLES LIKE '%kcp_plinth%'"
```

---

## Next Phase

**Phase 5: REST API** — `POST /kcp/v1/pricing/calculate`, catalog endpoint, configuration CRUD.
