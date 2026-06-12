# Phase 6 — Frontend Configurator

> **Status:** ✅ Complete  
> **Shortcode:** `[kitchen_configurator]`  
> **Load existing:** `[kitchen_configurator uuid="…"]` or `?kcp_config={uuid}`

---

## Deliverables

### PHP integration

| File | Purpose |
|------|---------|
| `src/Frontend/FrontendServiceProvider.php` | Registers shortcode + assets |
| `src/Frontend/Shortcode.php` | `[kitchen_configurator]` renderer |
| `src/Frontend/Assets.php` | Enqueues CSS + ES module JS |
| `templates/frontend/configurator.php` | Mount point template |

### JavaScript (ES6 modules)

| File | Purpose |
|------|---------|
| `assets/frontend/js/main.js` | Entry point |
| `assets/frontend/js/api/client.js` | REST API client (`kcp/v1`) |
| `assets/frontend/js/state/store.js` | Pub/sub state + helpers |
| `assets/frontend/js/utils/helpers.js` | DOM, format, debounce utilities |
| `assets/frontend/js/components/App.js` | Main orchestrator |
| `assets/frontend/js/components/LayoutStep.js` | Step 1: layout selection |
| `assets/frontend/js/components/CabinetsStep.js` | Step 2: cabinets + dimensions |
| `assets/frontend/js/components/FinishesStep.js` | Step 3: material, color, handle |
| `assets/frontend/js/components/ExtrasStep.js` | Step 4: accessories, worktop, plinth |
| `assets/frontend/js/components/SummaryStep.js` | Step 5: review + save |
| `assets/frontend/js/components/PriceSummary.js` | Live price sidebar |
| `assets/frontend/js/components/ProjectPanel.js` | Saved configurations list |

### Styles

| File | Purpose |
|------|---------|
| `assets/frontend/css/configurator.css` | Responsive UI (desktop + mobile) |

---

## Multi-step workflow

1. **Layout** — choose kitchen layout from catalog
2. **Cabinets** — add cabinets, set dimensions within allowed ranges
3. **Finishes** — per-cabinet material, color, handle
4. **Extras** — worktop, plinth, per-cabinet and kitchen-wide accessories
5. **Summary** — review line items, set title, save/update configuration

---

## Features

- Consumes all `kcp/v1` REST endpoints (catalog, pricing, configuration CRUD)
- Debounced real-time pricing via `POST /pricing/calculate`
- Save/load configurations with guest session (`localStorage` + `X-KCP-Session-Id`)
- Logged-in users authenticated via `X-WP-Nonce`
- Responsive 3-column layout (sidebar / content / price) collapsing on mobile
- Modular ES6 architecture (no framework dependency)

---

## Usage

Add to any page or post:

```
[kitchen_configurator]
```

Load a saved configuration:

```
[kitchen_configurator uuid="550e8400-e29b-41d4-a716-446655440000"]
```

---

## Boot config (`window.kcpConfigurator`)

| Key | Description |
|-----|-------------|
| `apiUrl` | REST base URL |
| `nonce` | WordPress REST nonce |
| `isLoggedIn` | Whether user is logged in |
| `currency` | Display currency |
| `i18n` | Translated UI strings |

---

## Next Phase

**Phase 7: WooCommerce Integration** — cart, checkout, order meta.
