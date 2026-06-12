# Phase 3 — Admin Panel CRUD Modules

## Deliverables

### Infrastructure
- `src/Contracts/RepositoryInterface.php`
- `src/Repositories/AbstractRepository.php`
- `src/Admin/AbstractCrudPage.php`
- `src/Admin/AdminServiceProvider.php`
- `src/Admin/Menu.php`
- `src/Admin/Assets.php`

### Domain
- Entities: Layout, CabinetCategory, Cabinet, Material, Color, Handle, Accessory, PricingRule, Configuration
- Enums: MaterialType, PricingRuleType

### Repositories (9)
- Layout, CabinetCategory, Cabinet, Material, Color, Handle, Accessory, PricingRule, Configuration

### Admin Pages (11)
| Page | Slug | Type |
|------|------|------|
| Dashboard | `kitchen-configurator-pro` | Stats overview |
| Layouts | `kcp-layouts` | CRUD |
| Cabinet Categories | `kcp-cabinet-categories` | CRUD |
| Cabinets | `kcp-cabinets` | CRUD |
| Materials | `kcp-materials` | CRUD |
| Colors | `kcp-colors` | CRUD |
| Handles | `kcp-handles` | CRUD |
| Accessories | `kcp-accessories` | CRUD |
| Pricing Rules | `kcp-pricing-rules` | CRUD |
| Configurations | `kcp-configurations` | Read-only list + view |
| Settings | `kcp-settings` | Plugin options |

### Templates
- `templates/admin/crud-list.php`
- `templates/admin/crud-form.php`
- `templates/admin/dashboard.php`
- `templates/admin/configurations-list.php`
- `templates/admin/configuration-view.php`
- `templates/admin/settings.php`
- `templates/admin/partials/admin-notice.php`

### Assets
- `assets/admin/css/admin.css`
- `assets/admin/js/admin.js`

## Architecture

- **AbstractCrudPage** — reusable list/add/edit/delete with nonce validation
- **AbstractRepository** — `$wpdb` CRUD with sanitization per entity
- **AdminServiceProvider** — registers repositories + pages in container
- Catalog saves bump `kcp_catalog_cache_version` for Phase 5 cache invalidation

## Access

Requires `manage_kcp` capability (assigned to administrators on activation).

## Next Phase

Phase 4: Pricing Engine (server-side calculators + rule engine).
