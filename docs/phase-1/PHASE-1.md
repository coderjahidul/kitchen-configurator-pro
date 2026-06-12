# Phase 1 — System Architecture, Schema & Diagrams

> **Status:** ✅ Complete  
> **Version:** 1.0.0  
> **Stack:** WordPress 6+, WooCommerce 9+, PHP 8.2+, MySQL 8.0+  
> **Reference:** [Keukenkastenfabriek Configurator](https://configurator.keukenkastenfabriek.nl/)

---

## Deliverables

| Document | Path | Description |
|----------|------|-------------|
| System Architecture | `docs/phase-1/ARCHITECTURE.md` | Layered design, request flows, pricing pipeline, security model |
| Folder Structure | `docs/phase-1/FOLDER-STRUCTURE.md` | Full directory tree, namespaces, naming conventions |
| Database Schema | `docs/phase-1/DATABASE-SCHEMA.md` | 11 custom tables, indexes, FKs, JSON fields, WC meta |
| ER Diagram | `docs/phase-1/ER-DIAGRAM.md` | Mermaid entity relationships, lifecycle, WC integration |
| Class Diagram | `docs/phase-1/CLASS-DIAGRAM.md` | OOP structure, SOLID mapping, REST endpoint map |
| SQL Reference | `database/schema/schema.sql` | Executable DDL (prefix placeholder `{prefix}`) |

---

## Architecture Summary

### Core Design Decision

**No WooCommerce variations for cabinet combinations.** Instead:

1. **Catalog tables** store layouts, cabinets, materials, colors, handles, accessories, and pricing rules
2. **Runtime pricing engine** calculates prices server-side from rules + catalog data
3. **JSON documents** store customer configurations and pricing snapshots
4. **Single WooCommerce container product** carries configuration UUID through cart → checkout → order

This scales to **thousands of logical combinations** without SKU explosion.

### Layered Architecture

```
Presentation  →  Admin CRUD | REST API | Frontend SPA
Application   →  Services (Configuration, Pricing, Catalog, Project, Quote)
Domain        →  Entities, DTOs, Value Objects, Enums, Contracts
Infrastructure→  Repositories, Migrations, WooCommerce Adapters, PDF
Persistence   →  MySQL custom tables + wp_posts/wp_postmeta (WC)
```

### Custom Database Tables (11)

| Table | Purpose |
|-------|---------|
| `kcp_migrations` | Schema version tracking |
| `kcp_layouts` | Kitchen layout types |
| `kcp_cabinet_categories` | Cabinet grouping |
| `kcp_cabinets` | Cabinet catalog + dimension ranges |
| `kcp_materials` | Front, carcass, worktop, plinth |
| `kcp_colors` | Colors per material |
| `kcp_handles` | Handle/knob options |
| `kcp_accessories` | Optional add-ons |
| `kcp_pricing_rules` | Flexible rule engine |
| `kcp_projects` | Customer project containers |
| `kcp_configurations` | Saved configs + pricing snapshots |
| `kcp_configuration_history` | Audit trail |

### Key Patterns

| Pattern | Implementation |
|---------|----------------|
| PSR-4 Autoloading | `KitchenConfiguratorPro\` via Composer |
| Repository | `RepositoryInterface` + `AbstractRepository` + entity repos |
| Service Layer | Business logic isolated from WP hooks and `$wpdb` |
| DI Container | Lightweight `Container` with bind/singleton |
| Service Providers | `AdminServiceProvider`, `ApiServiceProvider`, etc. |
| Migrations | Versioned classes tracked in `kcp_migrations` |

### Configuration JSON (stored in `kcp_configurations.configuration_json`)

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
  }
}
```

### Pricing Snapshot (stored in `pricing_snapshot_json`)

Server-generated on every calculate; includes `price_hash` for cart/checkout integrity validation.

---

## Implementation Progress

| Phase | Status |
|-------|--------|
| 1 — Architecture & Schema | ✅ Complete |
| 2 — Bootstrap & Migrations | ✅ Complete |
| 3 — Admin CRUD | ✅ Complete |
| 4 — Pricing Engine | ✅ Complete |
| 5 — REST API | Pending |
| 6 — Frontend Configurator | Pending |
| 7 — WooCommerce Integration | Pending |
| 8 — Security & Validation | Pending |
| 9 — Testing | Pending |
| 10 — Deployment | Pending |

---

## Approval Checklist

Before proceeding to **Phase 4 (Pricing Engine)**, please confirm:

- [ ] Database table design meets your catalog needs
- [ ] JSON configuration structure is acceptable
- [ ] Single WooCommerce container product approach is approved
- [ ] Namespace `KitchenConfiguratorPro` and prefix `kcp_` are acceptable
- [ ] Any additional catalog entities needed (e.g., worktops as separate entity, appliance cutouts)

---

## Next Phase

**Phase 5: REST API** — `POST /kcp/v1/pricing/calculate`, catalog endpoint, configuration CRUD.
