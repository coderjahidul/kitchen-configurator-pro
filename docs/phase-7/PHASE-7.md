# Phase 7 — WooCommerce Integration

> **Status:** ✅ Complete  
> **REST endpoint:** `POST /kcp/v1/cart/add`  
> **Container SKU:** `kcp-kitchen-configuration`

---

## Overview

Phase 7 connects saved kitchen configurations to WooCommerce using a **single hidden container product**. Configuration data, pricing snapshots, and integrity hashes travel through cart → checkout → order without WooCommerce variations.

---

## Deliverables

### Container product

| File | Purpose |
|------|---------|
| `src/Integration/WooCommerce/ProductManager.php` | Creates/manages hidden WC simple product; stores ID in `kcp_wc_product_id` |

### Cart integration

| File | Purpose |
|------|---------|
| `src/Services/CartIntegrationService.php` | Orchestrates prepare → add_to_cart → attach cart key |
| `src/Integration/WooCommerce/CartHandler.php` | Line price override, cart/checkout display |
| `src/Api/Controllers/CartController.php` | `POST /kcp/v1/cart/add` |

### Checkout & orders

| File | Purpose |
|------|---------|
| `src/Integration/WooCommerce/CheckoutHandler.php` | Server-side price hash validation before checkout |
| `src/Integration/WooCommerce/OrderHandler.php` | Persists config JSON + snapshot to order item meta |
| `src/Integration/WooCommerce/OrderMetaDisplay.php` | Admin + customer order views |

### Service wiring

| File | Purpose |
|------|---------|
| `src/Integration/WooCommerce/WooCommerceServiceProvider.php` | DI + WooCommerce hooks |
| `src/Plugin.php` | Boots WC integration when WooCommerce is active |
| `src/Activator.php` | Ensures container product on plugin activation |

### Configuration service extensions

| Method | Purpose |
|--------|---------|
| `prepare_for_cart()` | Recalculates pricing before cart add |
| `attach_cart_item()` | Stores `wc_cart_item_key` on configuration |
| `mark_ordered()` | Sets status `ordered` + `wc_order_id` after checkout |

---

## Cart item data keys

| Key | Description |
|-----|-------------|
| `kcp_config_uuid` | Configuration UUID |
| `kcp_price_hash` | Server price integrity hash |
| `kcp_total_price` | Calculated total (line item price) |
| `kcp_configuration_json` | Full configuration JSON |
| `kcp_pricing_snapshot_json` | Pricing snapshot JSON |
| `kcp_configuration_title` | Display title |
| `kcp_unique_key` | Unique cart line identifier |

---

## Order item meta keys

| Key | Description |
|-----|-------------|
| `_kcp_configuration_uuid` | Configuration UUID |
| `_kcp_configuration_json` | Full configuration JSON |
| `_kcp_pricing_snapshot_json` | Pricing snapshot |
| `_kcp_price_hash` | Price hash at purchase |
| `_kcp_total_price` | Total at purchase |

---

## Purchase flow

```
Configurator (Summary step)
    └─► Save configuration
    └─► POST /kcp/v1/cart/add { uuid }
            └─► ConfigurationService::prepare_for_cart()
            └─► PricingEngine::calculate()
            └─► WC()->cart->add_to_cart(container_product, cart_item_data)
    └─► Redirect to cart

Cart
    └─► woocommerce_before_calculate_totals → set line price from kcp_total_price
    └─► woocommerce_get_item_data → display project, cabinets, reference

Checkout
    └─► woocommerce_checkout_process / woocommerce_after_checkout_validation
            └─► Recalculate + verify price_hash + total

Order
    └─► woocommerce_checkout_create_order_line_item → persist meta
    └─► woocommerce_checkout_order_processed → mark configuration ordered
```

---

## Security & integrity

- **Server-only pricing** — cart line price always set from stored snapshot, never client input
- **Price hash** — checkout recalculates configuration and compares hash + total with `hash_equals()`
- **Ownership** — cart REST endpoint requires same auth as configuration mutations (user or guest session)
- **Ordered lock** — configurations with status `ordered` cannot be re-added to cart

---

## Frontend

- Summary step shows **Add to Cart** when WooCommerce is active and configuration is saved
- Saves configuration first (fresh pricing), then calls `POST /cart/add`
- Redirects to WooCommerce cart URL on success

---

## WooCommerce hooks used

| Hook | Handler |
|------|---------|
| `woocommerce_before_calculate_totals` | CartHandler |
| `woocommerce_get_item_data` | CartHandler |
| `woocommerce_cart_item_name` | CartHandler |
| `woocommerce_cart_item_price` | CartHandler |
| `woocommerce_checkout_process` | CheckoutHandler |
| `woocommerce_after_checkout_validation` | CheckoutHandler |
| `woocommerce_checkout_create_order_line_item` | OrderHandler |
| `woocommerce_checkout_order_processed` | OrderHandler |
| `woocommerce_hidden_order_itemmeta` | OrderMetaDisplay |
| `woocommerce_after_order_itemmeta` | OrderMetaDisplay |
| `woocommerce_order_item_meta_end` | OrderMetaDisplay |

---

## Next phase

Phase 8 — Security & validation hardening.
