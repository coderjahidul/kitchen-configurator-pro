# Kitchen Configurator Pro — Database Schema

## Overview

All custom tables use the WordPress table prefix (`{$wpdb->prefix}`) plus `kcp_` prefix.

**Example:** `wp_kcp_cabinets`

Storage engine: **InnoDB** (foreign keys, transactions)  
Charset: **utf8mb4_unicode_ci**

---

## Entity Relationship Summary

```
kcp_layouts
kcp_cabinet_categories
kcp_cabinets ──────────► kcp_cabinet_categories
kcp_materials
kcp_colors ────────────► kcp_materials
kcp_handles
kcp_accessories
kcp_pricing_rules        (polymorphic: entity_type + entity_id)
kcp_projects ──────────► wp_users (user_id)
kcp_configurations ─────► kcp_layouts, kcp_projects, wp_users
kcp_configuration_history ► kcp_configurations
kcp_migrations           (internal version tracking)
```

---

## Table Definitions

### 1. `kcp_migrations`

Internal migration version tracking.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| version | VARCHAR(20) | NOT NULL, UNIQUE | e.g. `1.0.0` |
| class_name | VARCHAR(191) | NOT NULL | Migration class FQCN |
| executed_at | DATETIME | NOT NULL | UTC execution time |

---

### 2. `kcp_layouts`

Kitchen layout types (straight, L-shape, U-shape, island, etc.).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| slug | VARCHAR(100) | NOT NULL, UNIQUE | URL-safe identifier |
| name | VARCHAR(191) | NOT NULL | Display name |
| description | TEXT | NULL | |
| thumbnail_url | VARCHAR(500) | NULL | Layout preview image |
| config_json | LONGTEXT | NULL | Layout-specific constraints (max dimensions, zones) |
| sort_order | INT | NOT NULL, DEFAULT 0 | |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| created_at | DATETIME | NOT NULL | |
| updated_at | DATETIME | NOT NULL | |

**Indexes:** `idx_active_sort` (`is_active`, `sort_order`)

---

### 3. `kcp_cabinet_categories`

Cabinet grouping (base, wall, tall, corner, appliance housing).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| slug | VARCHAR(100) | NOT NULL, UNIQUE | |
| name | VARCHAR(191) | NOT NULL | |
| description | TEXT | NULL | |
| sort_order | INT | NOT NULL, DEFAULT 0 | |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| created_at | DATETIME | NOT NULL | |
| updated_at | DATETIME | NOT NULL | |

---

### 4. `kcp_cabinets`

Cabinet catalog with dimension ranges and base pricing.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| category_id | BIGINT UNSIGNED | NOT NULL, FK | → kcp_cabinet_categories.id |
| slug | VARCHAR(100) | NOT NULL, UNIQUE | |
| name | VARCHAR(191) | NOT NULL | |
| description | TEXT | NULL | |
| sku_prefix | VARCHAR(50) | NULL | Internal reference prefix |
| default_width | INT UNSIGNED | NOT NULL | mm |
| default_height | INT UNSIGNED | NOT NULL | mm |
| default_depth | INT UNSIGNED | NOT NULL | mm |
| min_width | INT UNSIGNED | NOT NULL | mm |
| max_width | INT UNSIGNED | NOT NULL | mm |
| min_height | INT UNSIGNED | NOT NULL | mm |
| max_height | INT UNSIGNED | NOT NULL | mm |
| min_depth | INT UNSIGNED | NOT NULL | mm |
| max_depth | INT UNSIGNED | NOT NULL | mm |
| width_step | INT UNSIGNED | NOT NULL, DEFAULT 10 | mm increment |
| height_step | INT UNSIGNED | NOT NULL, DEFAULT 10 | |
| depth_step | INT UNSIGNED | NOT NULL, DEFAULT 10 | |
| base_price | DECIMAL(12,2) | NOT NULL, DEFAULT 0.00 | |
| dimension_price_json | LONGTEXT | NULL | Per-mm surcharge rules |
| image_url | VARCHAR(500) | NULL | |
| sort_order | INT | NOT NULL, DEFAULT 0 | |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| created_at | DATETIME | NOT NULL | |
| updated_at | DATETIME | NOT NULL | |

**Indexes:**
- `idx_category_active` (`category_id`, `is_active`)
- `idx_active_sort` (`is_active`, `sort_order`)

**FK:** `category_id` → `kcp_cabinet_categories(id)` ON DELETE RESTRICT

---

### 5. `kcp_materials`

Materials (front panels, carcass, worktop, etc.).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| slug | VARCHAR(100) | NOT NULL, UNIQUE | |
| name | VARCHAR(191) | NOT NULL | |
| material_type | VARCHAR(50) | NOT NULL | `front`, `carcass`, `worktop`, `plinth` |
| description | TEXT | NULL | |
| price_modifier | DECIMAL(12,2) | NOT NULL, DEFAULT 0.00 | Flat modifier per cabinet |
| price_per_sqm | DECIMAL(12,4) | NULL | Area-based pricing |
| price_multiplier | DECIMAL(8,4) | NOT NULL, DEFAULT 1.0000 | Multiplier on base |
| thumbnail_url | VARCHAR(500) | NULL | |
| sort_order | INT | NOT NULL, DEFAULT 0 | |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| created_at | DATETIME | NOT NULL | |
| updated_at | DATETIME | NOT NULL | |

**Indexes:** `idx_type_active` (`material_type`, `is_active`)

---

### 6. `kcp_colors`

Colors linked to materials.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| material_id | BIGINT UNSIGNED | NOT NULL, FK | → kcp_materials.id |
| slug | VARCHAR(100) | NOT NULL | |
| name | VARCHAR(191) | NOT NULL | |
| hex_code | CHAR(7) | NULL | e.g. `#FFFFFF` |
| price_modifier | DECIMAL(12,2) | NOT NULL, DEFAULT 0.00 | |
| thumbnail_url | VARCHAR(500) | NULL | |
| sort_order | INT | NOT NULL, DEFAULT 0 | |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| created_at | DATETIME | NOT NULL | |
| updated_at | DATETIME | NOT NULL | |

**Indexes:**
- `UNIQUE idx_material_slug` (`material_id`, `slug`)
- `idx_material_active` (`material_id`, `is_active`)

**FK:** `material_id` → `kcp_materials(id)` ON DELETE CASCADE

---

### 7. `kcp_handles`

Handle/knob options.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| slug | VARCHAR(100) | NOT NULL, UNIQUE | |
| name | VARCHAR(191) | NOT NULL | |
| description | TEXT | NULL | |
| price | DECIMAL(12,2) | NOT NULL, DEFAULT 0.00 | Per cabinet |
| thumbnail_url | VARCHAR(500) | NULL | |
| sort_order | INT | NOT NULL, DEFAULT 0 | |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| created_at | DATETIME | NOT NULL | |
| updated_at | DATETIME | NOT NULL | |

---

### 8. `kcp_accessories`

Optional add-ons (soft-close, lighting, organizers).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| slug | VARCHAR(100) | NOT NULL, UNIQUE | |
| name | VARCHAR(191) | NOT NULL | |
| category | VARCHAR(50) | NOT NULL, DEFAULT 'general' | |
| description | TEXT | NULL | |
| price | DECIMAL(12,2) | NOT NULL, DEFAULT 0.00 | |
| is_per_cabinet | TINYINT(1) | NOT NULL, DEFAULT 1 | 0 = per kitchen |
| thumbnail_url | VARCHAR(500) | NULL | |
| sort_order | INT | NOT NULL, DEFAULT 0 | |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| created_at | DATETIME | NOT NULL | |
| updated_at | DATETIME | NOT NULL | |

**Indexes:** `idx_category_active` (`category`, `is_active`)

---

### 9. `kcp_pricing_rules`

Flexible rule engine for complex pricing logic.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| name | VARCHAR(191) | NOT NULL | Admin label |
| rule_type | VARCHAR(50) | NOT NULL | `surcharge`, `discount`, `multiplier`, `fixed` |
| entity_type | VARCHAR(50) | NULL | `cabinet`, `material`, `layout`, `global` |
| entity_id | BIGINT UNSIGNED | NULL | Polymorphic reference |
| conditions_json | LONGTEXT | NOT NULL | When rule applies |
| calculation_json | LONGTEXT | NOT NULL | How to calculate |
| priority | INT | NOT NULL, DEFAULT 100 | Lower = earlier |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| valid_from | DATETIME | NULL | |
| valid_until | DATETIME | NULL | |
| created_at | DATETIME | NOT NULL | |
| updated_at | DATETIME | NOT NULL | |

**Indexes:**
- `idx_active_priority` (`is_active`, `priority`)
- `idx_entity` (`entity_type`, `entity_id`)

**Example `conditions_json`:**

```json
{
  "all": [
    { "field": "cabinet.width", "operator": ">", "value": 800 },
    { "field": "material.type", "operator": "=", "value": "front" }
  ]
}
```

**Example `calculation_json`:**

```json
{
  "type": "per_mm",
  "field": "cabinet.width",
  "rate": 0.15,
  "label": "Oversize width surcharge"
}
```

---

### 10. `kcp_projects`

Customer project containers for configuration history.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| uuid | CHAR(36) | NOT NULL, UNIQUE | Public identifier |
| user_id | BIGINT UNSIGNED | NULL | → wp_users.ID (NULL = guest) |
| session_id | VARCHAR(64) | NULL | Guest session tracking |
| name | VARCHAR(191) | NOT NULL | Project name |
| created_at | DATETIME | NOT NULL | |
| updated_at | DATETIME | NOT NULL | |

**Indexes:**
- `idx_user` (`user_id`)
- `idx_session` (`session_id`)

---

### 11. `kcp_configurations`

Saved customer kitchen configurations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| uuid | CHAR(36) | NOT NULL, UNIQUE | Public identifier |
| project_id | BIGINT UNSIGNED | NULL, FK | → kcp_projects.id |
| layout_id | BIGINT UNSIGNED | NOT NULL, FK | → kcp_layouts.id |
| user_id | BIGINT UNSIGNED | NULL | → wp_users.ID |
| session_id | VARCHAR(64) | NULL | Guest session |
| title | VARCHAR(191) | NOT NULL | |
| configuration_json | LONGTEXT | NOT NULL | Full config document |
| pricing_snapshot_json | LONGTEXT | NULL | Last server calculation |
| total_price | DECIMAL(12,2) | NOT NULL, DEFAULT 0.00 | Denormalized for queries |
| price_hash | VARCHAR(64) | NULL | SHA-256 integrity hash |
| status | VARCHAR(20) | NOT NULL, DEFAULT 'draft' | draft, saved, quoted, ordered, archived |
| wc_order_id | BIGINT UNSIGNED | NULL | → wp_posts.ID when ordered |
| wc_cart_item_key | VARCHAR(64) | NULL | Active cart reference |
| quoted_at | DATETIME | NULL | PDF quote timestamp |
| created_at | DATETIME | NOT NULL | |
| updated_at | DATETIME | NOT NULL | |

**Indexes:**
- `idx_user_status` (`user_id`, `status`)
- `idx_session` (`session_id`)
- `idx_project` (`project_id`)
- `idx_status_updated` (`status`, `updated_at`)
- `idx_wc_order` (`wc_order_id`)

**FK:**
- `project_id` → `kcp_projects(id)` ON DELETE SET NULL
- `layout_id` → `kcp_layouts(id)` ON DELETE RESTRICT

---

### 12. `kcp_configuration_history`

Audit trail for configuration changes.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| configuration_id | BIGINT UNSIGNED | NOT NULL, FK | → kcp_configurations.id |
| configuration_json | LONGTEXT | NOT NULL | Snapshot at this point |
| pricing_snapshot_json | LONGTEXT | NULL | |
| action | VARCHAR(50) | NOT NULL | `created`, `updated`, `quoted`, `ordered` |
| actor_user_id | BIGINT UNSIGNED | NULL | Who made the change |
| created_at | DATETIME | NOT NULL | |

**Indexes:** `idx_configuration_created` (`configuration_id`, `created_at`)

**FK:** `configuration_id` → `kcp_configurations(id)` ON DELETE CASCADE

---

## WordPress Options (not custom tables)

| Option Key | Purpose |
|------------|---------|
| `kcp_db_version` | Current schema version |
| `kcp_wc_product_id` | WooCommerce container product post ID |
| `kcp_settings` | Serialized plugin settings |
| `kcp_catalog_cache_version` | Cache busting increment |

---

## WooCommerce Order Meta (not custom tables)

Stored on `woocommerce_order_itemmeta`:

| Meta Key | Description |
|----------|-------------|
| `_kcp_configuration_uuid` | Reference to kcp_configurations.uuid |
| `_kcp_configuration_json` | Full config snapshot at purchase |
| `_kcp_pricing_snapshot_json` | Price breakdown at purchase |
| `_kcp_price_hash` | Integrity verification |
| `_kcp_total_price` | Line item total |

---

## Scalability Notes

| Concern | Solution |
|---------|----------|
| Thousands of combinations | Zero extra rows; runtime calculation |
| Large JSON documents | LONGTEXT; typical config < 50KB |
| Configuration list queries | Denormalized `total_price`, `status`, indexed `updated_at` |
| History growth | Partition by date or archive job (Phase 10) |
| Full-text search on configs | Optional `configuration_search` generated column in future migration |

---

## Data Volume Estimates (Production)

| Table | Expected rows (Year 1) |
|-------|------------------------|
| kcp_cabinets | 50–200 |
| kcp_materials | 20–50 |
| kcp_colors | 100–300 |
| kcp_handles | 20–50 |
| kcp_accessories | 30–100 |
| kcp_pricing_rules | 50–200 |
| kcp_configurations | 10,000–100,000 |
| kcp_configuration_history | 50,000–500,000 |

---

*End of Database Schema Document*
