# Kitchen Configurator Pro — Phase 1: System Architecture

> **Version:** 1.0.0  
> **Target:** WordPress 6+, WooCommerce 9+, PHP 8.2+, MySQL 8.0+  
> **Reference:** [Keukenkastenfabriek Configurator](https://configurator.keukenkastenfabriek.nl/)

---

## 1. Executive Summary

Kitchen Configurator Pro (KCP) is a production-grade WordPress plugin that enables customers to design custom kitchen layouts online, configure cabinets with dimensions and finishes, receive server-calculated pricing, save projects, and purchase through WooCommerce — **without** using WooCommerce product variations for cabinet combinations.

Instead, KCP uses:

- **Catalog tables** for layouts, cabinets, materials, colors, handles, and accessories
- **Rule-based pricing engine** for dynamic, server-side price calculation
- **JSON documents** for customer configuration snapshots
- **A single WooCommerce "container" product** that carries configuration references through cart → checkout → order

This architecture scales to **thousands of logical combinations** because combinations are computed at runtime from rules — not stored as SKUs or variations.

---

## 2. Architectural Principles

| Principle | Application |
|-----------|-------------|
| **SOLID** | Single-responsibility services; interfaces for repositories; dependency injection via container |
| **Repository + Service** | Repositories handle persistence; services contain business logic |
| **Server-authoritative pricing** | Frontend displays prices from REST API only; never trusts client calculations |
| **WordPress-native** | Hooks, capabilities, nonces, `$wpdb`, transients, REST API |
| **WooCommerce boundary** | WC integration isolated in `Integration/WooCommerce/` namespace |
| **PSR-4 autoloading** | `KitchenConfiguratorPro\` namespace via Composer |
| **Scalability** | Indexed tables, JSON for flexible config, pricing rules with priority, caching layer |

---

## 3. High-Level System Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           PRESENTATION LAYER                                 │
├──────────────────────┬──────────────────────┬───────────────────────────────┤
│  Frontend SPA        │  WordPress Admin     │  REST API (wp-json/kcp/v1)    │
│  (ES6 Configurator)  │  (CRUD Panels)       │  (Controllers)                │
└──────────┬───────────┴──────────┬───────────┴──────────────┬────────────────┘
           │                      │                          │
           ▼                      ▼                          ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         APPLICATION LAYER (Services)                       │
├─────────────────────────────────────────────────────────────────────────────┤
│ ConfigurationService │ PricingEngine │ CatalogService │ ProjectService      │
│ CartIntegrationService │ QuoteService (PDF) │ ValidationService             │
└──────────┬──────────────────────────────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           DOMAIN LAYER                                       │
├─────────────────────────────────────────────────────────────────────────────┤
│ Entities │ Value Objects │ DTOs │ Enums │ Domain Exceptions │ Contracts     │
└──────────┬──────────────────────────────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        INFRASTRUCTURE LAYER                                  │
├──────────────────────┬──────────────────────┬───────────────────────────────┤
│ Repositories (MySQL) │ Migrations           │ WooCommerce Adapters          │
│ PDF Generator        │ Cache (Transients)   │ Security (Nonces, Caps)       │
└──────────────────────┴──────────────────────┴───────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  MySQL Custom Tables  │  wp_posts (WC Product)  │  wp_postmeta (Order Meta) │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Request Flow Diagrams

### 4.1 Configuration + Pricing Flow

```
Customer (Browser)
    │
    ├─► GET  /kcp/v1/catalog          → CatalogService → Repositories → JSON catalog
    │
    ├─► POST /kcp/v1/configurations   → ConfigurationService → save JSON draft
    │
    ├─► POST /kcp/v1/pricing/calculate → PricingEngine
    │         │
    │         ├─ Validate configuration schema
    │         ├─ Load cabinet/material/handle/accessory entities
    │         ├─ Apply pricing rules (priority order)
    │         └─ Return pricing snapshot (line items + total)
    │
    └─► PUT  /kcp/v1/configurations/{uuid} → update + recalculate server-side
```

### 4.2 WooCommerce Purchase Flow

```
Customer clicks "Add to Cart"
    │
    ├─► POST /kcp/v1/cart/add
    │         │
    │         ├─ PricingEngine::calculate()  (authoritative price)
    │         ├─ ConfigurationService::lockForCart()
    │         └─ CartIntegrationService::addConfiguration()
    │                   │
    │                   └─ WC Cart item data: { kcp_config_uuid, kcp_price_hash }
    │
    ├─► woocommerce_before_calculate_totals
    │         └─ Set line item price from server snapshot (never client price)
    │
    ├─► Checkout
    │         └─ Validate price hash + configuration integrity
    │
    └─► Order placed
              └─ Order item meta: full configuration JSON + pricing snapshot
```

---

## 5. Why Not WooCommerce Variations?

| Approach | Problem at scale |
|----------|------------------|
| WC Variations per combination | Explodes SKU count; admin unmanageable; import/sync nightmare |
| **KCP approach** | One container product; combinations = runtime calculation; order stores snapshot |

WooCommerce is used for **payment, tax, shipping hooks, and order management** — not as the configuration data store.

---

## 6. Configuration JSON Schema (Conceptual)

Customer configurations are stored as versioned JSON documents:

```json
{
  "schema_version": "1.0",
  "layout_id": 3,
  "title": "My Kitchen Project",
  "cabinets": [
    {
      "cabinet_id": 12,
      "position": { "x": 0, "y": 0, "rotation": 0 },
      "dimensions": { "width": 600, "height": 720, "depth": 560 },
      "material_id": 5,
      "color_id": 18,
      "handle_id": 7,
      "accessories": [101, 102]
    }
  ],
  "global_options": {
    "worktop_material_id": 2,
    "worktop_color_id": 9
  },
  "metadata": {
    "created_at": "2026-06-10T12:00:00Z",
    "client_version": "1.0.0"
  }
}
```

**Pricing snapshot** (stored separately, regenerated on every calculate):

```json
{
  "calculated_at": "2026-06-10T12:05:00Z",
  "currency": "EUR",
  "line_items": [
    {
      "type": "cabinet",
      "reference_id": 12,
      "label": "Base Cabinet 60cm",
      "quantity": 1,
      "unit_price": 450.00,
      "subtotal": 450.00,
      "breakdown": [
        { "rule": "base_price", "amount": 380.00 },
        { "rule": "material_premium", "amount": 45.00 },
        { "rule": "dimension_surcharge", "amount": 25.00 }
      ]
    }
  ],
  "subtotal": 4500.00,
  "tax": 945.00,
  "total": 5445.00,
  "price_hash": "sha256:abc123..."
}
```

---

## 7. Pricing Engine Design

The pricing engine is a **pipeline of calculators** applied in priority order:

```
Configuration DTO
    │
    ▼
┌─────────────────┐
│ Schema Validator │
└────────┬────────┘
         ▼
┌─────────────────┐     ┌──────────────────┐
│ Base Price      │ ──► │ Per-cabinet base │
│ Calculator      │     │ from kcp_cabinets│
└────────┬────────┘     └──────────────────┘
         ▼
┌─────────────────┐     ┌──────────────────┐
│ Dimension       │ ──► │ Width/height/depth│
│ Calculator      │     │ surcharges        │
└────────┬────────┘     └──────────────────┘
         ▼
┌─────────────────┐     ┌──────────────────┐
│ Material/Color  │ ──► │ Modifiers from    │
│ Calculator      │     │ catalog tables    │
└────────┬────────┘     └──────────────────┘
         ▼
┌─────────────────┐     ┌──────────────────┐
│ Accessory       │ ──► │ Sum accessory     │
│ Calculator      │     │ prices            │
└────────┬────────┘     └──────────────────┘
         ▼
┌─────────────────┐     ┌──────────────────┐
│ Rule Engine     │ ──► │ kcp_pricing_rules │
│ (custom rules)  │     │ (JSON conditions) │
└────────┬────────┘     └──────────────────┘
         ▼
    PricingSnapshot DTO + price_hash
```

**Price hash** prevents cart tampering: checkout re-validates hash against live calculation.

---

## 8. Security Model (Planned — Phase 8)

| Concern | Strategy |
|---------|----------|
| REST authentication | Cookie auth for logged-in users; nonce for guests; `manage_kcp` capability for admin |
| Price tampering | Server-only pricing; `price_hash` validation at cart/checkout |
| SQL injection | `$wpdb->prepare()` everywhere; repository layer only |
| XSS | `wp_kses_post`, escaped output in admin |
| CSRF | WordPress nonces on all mutating endpoints |
| Rate limiting | Transient-based throttling on pricing endpoint |

---

## 9. Caching Strategy

| Data | Cache | TTL |
|------|-------|-----|
| Full catalog | `kcp_catalog_v1` transient | 1 hour (invalidated on admin save) |
| Pricing rules | `kcp_pricing_rules` transient | 1 hour |
| Individual configuration | No cache (always fresh pricing) | — |
| PDF quotes | Generated on-demand, stored in uploads | 30 days |

---

## 10. Scalability Considerations

1. **No combination explosion** — runtime calculation from ~50 cabinets × ~20 materials × dimension ranges = thousands of logical combos, zero extra rows
2. **Indexed foreign keys** — all `entity_id`, `user_id`, `uuid` columns indexed
3. **JSON columns** — `configuration_json` and `pricing_snapshot_json` use MySQL `LONGTEXT` with optional generated columns for hot fields later
4. **Pagination** — admin lists and project history use cursor/offset pagination
5. **Async PDF** — Phase 7+ can queue PDF generation via Action Scheduler (WC dependency)
6. **Read replicas** — repository layer abstracts DB; future swap to read replica for catalog reads

---

## 11. WordPress Integration Points

| Hook | Purpose |
|------|---------|
| `plugins_loaded` | Bootstrap plugin, load text domain |
| `init` | Register CPT (if needed), rewrite rules |
| `rest_api_init` | Register REST routes |
| `admin_menu` | Admin panels |
| `woocommerce_init` | WC integration bootstrap |
| `woocommerce_add_cart_item_data` | Attach configuration UUID |
| `woocommerce_before_calculate_totals` | Override line item price |
| `woocommerce_checkout_create_order_line_item` | Persist order meta |
| `woocommerce_order_item_meta` | Display configuration in admin/emails |

---

## 12. Technology Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Autoloading | Composer PSR-4 | Industry standard, testable |
| DI Container | Lightweight custom container | No heavy framework; WP-friendly |
| Migrations | Custom versioned migration classes | Full control; no external ORM |
| Frontend | Vanilla ES6 modules + Vite build (Phase 6) | No React dependency unless requested |
| PDF | TCPDF or Dompdf via Composer | Server-side quote generation |
| IDs | UUID v4 for public config references | Prevents enumeration attacks |
| Money | Store as `DECIMAL(12,2)` | Never use floats for currency |

---

## 13. Phase Roadmap Reference

| Phase | Deliverable |
|-------|-------------|
| **1** | Architecture, schema, diagrams *(this document)* |
| 2 | Plugin bootstrap, Composer, container, migrations |
| 3 | Admin CRUD modules |
| 4 | Pricing engine |
| 5 | REST API |
| 6 | Frontend configurator |
| 7 | WooCommerce integration |
| 8 | Security and validation |
| 9 | Testing |
| 10 | Deployment |

---

## 14. Approval Checklist

Before proceeding to Phase 2, please confirm:

- [ ] Database table design meets your catalog needs
- [ ] JSON configuration structure is acceptable
- [ ] Single WooCommerce container product approach is approved
- [ ] Namespace `KitchenConfiguratorPro` and prefix `kcp_` are acceptable
- [ ] Any additional catalog entities needed (e.g., worktops as separate entity, appliance cutouts)

---

*End of Phase 1 Architecture Document*
