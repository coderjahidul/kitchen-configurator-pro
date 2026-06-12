# Kitchen Configurator Pro — Entity Relationship Diagram

## Mermaid ER Diagram

```mermaid
erDiagram
    WP_USERS {
        bigint ID PK
        varchar user_login
        varchar user_email
    }

    WP_POSTS {
        bigint ID PK
        varchar post_type
        varchar post_title
    }

    KCP_LAYOUTS {
        bigint id PK
        varchar slug UK
        varchar name
        text description
        varchar thumbnail_url
        longtext config_json
        int sort_order
        tinyint is_active
        datetime created_at
        datetime updated_at
    }

    KCP_CABINET_CATEGORIES {
        bigint id PK
        varchar slug UK
        varchar name
        text description
        int sort_order
        tinyint is_active
        datetime created_at
        datetime updated_at
    }

    KCP_CABINETS {
        bigint id PK
        bigint category_id FK
        varchar slug UK
        varchar name
        int default_width
        int default_height
        int default_depth
        int min_width
        int max_width
        decimal base_price
        longtext dimension_price_json
        tinyint is_active
    }

    KCP_MATERIALS {
        bigint id PK
        varchar slug UK
        varchar name
        varchar material_type
        decimal price_modifier
        decimal price_per_sqm
        decimal price_multiplier
        tinyint is_active
    }

    KCP_COLORS {
        bigint id PK
        bigint material_id FK
        varchar slug
        varchar name
        char hex_code
        decimal price_modifier
        tinyint is_active
    }

    KCP_HANDLES {
        bigint id PK
        varchar slug UK
        varchar name
        decimal price
        tinyint is_active
    }

    KCP_ACCESSORIES {
        bigint id PK
        varchar slug UK
        varchar name
        varchar category
        decimal price
        tinyint is_per_cabinet
        tinyint is_active
    }

    KCP_PRICING_RULES {
        bigint id PK
        varchar name
        varchar rule_type
        varchar entity_type
        bigint entity_id
        longtext conditions_json
        longtext calculation_json
        int priority
        tinyint is_active
    }

    KCP_PROJECTS {
        bigint id PK
        char uuid UK
        bigint user_id FK
        varchar session_id
        varchar name
        datetime created_at
        datetime updated_at
    }

    KCP_CONFIGURATIONS {
        bigint id PK
        char uuid UK
        bigint project_id FK
        bigint layout_id FK
        bigint user_id FK
        varchar session_id
        varchar title
        longtext configuration_json
        longtext pricing_snapshot_json
        decimal total_price
        varchar price_hash
        varchar status
        bigint wc_order_id FK
        varchar wc_cart_item_key
        datetime quoted_at
    }

    KCP_CONFIGURATION_HISTORY {
        bigint id PK
        bigint configuration_id FK
        longtext configuration_json
        longtext pricing_snapshot_json
        varchar action
        bigint actor_user_id FK
        datetime created_at
    }

    KCP_MIGRATIONS {
        bigint id PK
        varchar version UK
        varchar class_name
        datetime executed_at
    }

  %% Catalog relationships
    KCP_CABINET_CATEGORIES ||--o{ KCP_CABINETS : "has many"
    KCP_MATERIALS ||--o{ KCP_COLORS : "has many"

  %% Configuration relationships
    KCP_LAYOUTS ||--o{ KCP_CONFIGURATIONS : "used by"
    KCP_PROJECTS ||--o{ KCP_CONFIGURATIONS : "contains"
    KCP_CONFIGURATIONS ||--o{ KCP_CONFIGURATION_HISTORY : "audited by"

  %% WordPress integrations
    WP_USERS ||--o{ KCP_PROJECTS : "owns"
    WP_USERS ||--o{ KCP_CONFIGURATIONS : "owns"
    WP_USERS ||--o{ KCP_CONFIGURATION_HISTORY : "acted"
    WP_POSTS ||--o{ KCP_CONFIGURATIONS : "order reference"

  %% Polymorphic (logical, not FK enforced)
    KCP_PRICING_RULES }o--o| KCP_CABINETS : "entity_type=cabinet"
    KCP_PRICING_RULES }o--o| KCP_MATERIALS : "entity_type=material"
    KCP_PRICING_RULES }o--o| KCP_LAYOUTS : "entity_type=layout"
```

---

## Simplified Catalog Diagram

```mermaid
flowchart TB
    subgraph Catalog["Product Catalog"]
        L[KCP_LAYOUTS]
        CC[KCP_CABINET_CATEGORIES]
        C[KCP_CABINETS]
        M[KCP_MATERIALS]
        CO[KCP_COLORS]
        H[KCP_HANDLES]
        A[KCP_ACCESSORIES]
        PR[KCP_PRICING_RULES]
    end

    CC --> C
    M --> CO
    PR -.->|polymorphic| C
    PR -.->|polymorphic| M
    PR -.->|polymorphic| L
```

---

## Configuration Lifecycle Diagram

```mermaid
stateDiagram-v2
    [*] --> draft: Customer starts configurator
    draft --> saved: Save configuration
    saved --> draft: Edit configuration
    saved --> quoted: Generate PDF quote
    quoted --> saved: Edit after quote
    saved --> ordered: Add to cart + checkout
    quoted --> ordered: Purchase from quote
    ordered --> archived: Order completed
    draft --> archived: Abandoned cleanup
    archived --> [*]
```

---

## WooCommerce Integration Diagram

```mermaid
flowchart LR
    subgraph KCP["Kitchen Configurator Pro"]
        CFG[KCP_CONFIGURATIONS]
        PE[Pricing Engine]
    end

    subgraph WC["WooCommerce"]
        PROD[Container Product<br/>single SKU]
        CART[Cart Item Meta]
        ORDER[Order Item Meta]
    end

    CFG --> PE
    PE -->|price_hash + total| CART
    CART -->|kcp_config_uuid| PROD
    CART --> ORDER
    CFG -->|wc_order_id| ORDER
    ORDER -->|_kcp_configuration_json| CFG
```

---

## JSON Reference Model (Logical, not stored as tables)

Configuration JSON references catalog entities by ID — no FK at JSON level:

```mermaid
flowchart TD
    CONFIG[configuration_json]
    CONFIG --> LID[layout_id → KCP_LAYOUTS]
    CONFIG --> CAB[cabinets array]
    CAB --> CID[cabinet_id → KCP_CABINETS]
    CAB --> DIM[dimensions inline]
    CAB --> MID[material_id → KCP_MATERIALS]
    CAB --> COID[color_id → KCP_COLORS]
    CAB --> HID[handle_id → KCP_HANDLES]
    CAB --> AIDS[accessories → KCP_ACCESSORIES]
    CONFIG --> GLOBAL[global_options]
    GLOBAL --> WM[worktop_material_id → KCP_MATERIALS]
```

---

## Table Count Summary

| Category | Tables | Purpose |
|----------|--------|---------|
| System | 1 | Migration tracking |
| Catalog | 7 | Layouts, cabinets, materials, colors, handles, accessories, rules |
| Customer data | 3 | Projects, configurations, history |
| **Total custom** | **11** | |
| WordPress native | 2+ | wp_users, wp_posts (WC orders/products) |
| WooCommerce meta | — | Order item meta for config snapshots |

---

*End of ER Diagram Document*
