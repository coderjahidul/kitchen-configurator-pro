# Phase 8 — Security & Validation

> **Status:** ✅ Complete

---

## Overview

Phase 8 hardens Kitchen Configurator Pro against invalid input, unauthorized access, price tampering, and untracked configuration changes. All validation runs server-side; the frontend never supplies authoritative prices.

---

## Deliverables

### Security services

| File | Purpose |
|------|---------|
| `src/Security/SecurityLogger.php` | Logs auth failures, validation errors, price integrity failures, rate limits |
| `src/Security/RateLimiter.php` | Transient-based throttling for pricing and cart endpoints |
| `src/Security/ConfigurationSchemaValidator.php` | Validates JSON structure, rejects forbidden fields, enforces size limits |
| `src/Security/RestInputValidator.php` | Shared REST `validate_callback` helpers |
| `src/Security/SecurityServiceProvider.php` | DI registration for security services |

### Audit trail

| File | Purpose |
|------|---------|
| `src/Repositories/ConfigurationHistoryRepository.php` | Writes to `kcp_configuration_history` |
| `src/Services/ConfigurationAuditService.php` | Records create/update/delete/cart/order events |

### Hardened integrations

| Area | Changes |
|------|---------|
| `RestAuth` | Returns `WP_Error` on failure; logs auth failures |
| REST controllers | `validate_callback` on all inputs; rate limits on pricing/cart |
| `ConfigurationService` | Schema validation before sanitize; audit on lifecycle events |
| `CartHandler` | Blocks container product without KCP meta; re-verifies price hash in cart |
| `CheckoutHandler` | Logs price integrity failures |
| `AbstractCrudPage` | Type-aware field sanitization; logs failed admin nonces |
| Frontend `client.js` | Generates guest session ID on init |

---

## Input validation

### REST layer

- Route args use `sanitize_callback` + `validate_callback`
- Shared schema via `RestInputValidator::configuration_args()`
- UUID, pagination, layout ID, cabinets array validated before handlers run

### Configuration schema

| Rule | Limit |
|------|-------|
| Schema version | `1.0` only |
| Max cabinets | 50 |
| Max accessories | 30 per cabinet / global |
| Max title length | 200 characters |
| Max payload size | 512 KB |

### Forbidden client fields

These are rejected if present in request payloads:

`total_price`, `price_hash`, `pricing_snapshot_json`, `pricing`, `status`, `uuid`, `user_id`, `session_id`, `wc_order_id`, `wc_cart_item_key`, `created_at`, `updated_at`

---

## Authentication & authorization

| Context | Mechanism |
|---------|-----------|
| Logged-in REST | `X-WP-Nonce` (`wp_rest`) |
| Guest REST | `X-KCP-Session-Id` header (generated client-side on first load) |
| Admin | `manage_kcp` capability + WordPress nonces on all forms |
| Configuration ownership | User ID or guest session match; admins bypass |

---

## Price integrity

1. **Server-only pricing** — `PricingEngine` calculates all totals; clients cannot submit prices
2. **Forbidden fields** — schema validator strips/rejects price-related input
3. **Cart recalculation** — `prepare_for_cart()` recalculates before add-to-cart
4. **Cart totals hook** — re-verifies `price_hash` on every totals calculation; removes invalid items
5. **Checkout** — `CheckoutHandler` recalculates and compares hash + total with `hash_equals()`

---

## Rate limiting

| Endpoint | Limit |
|----------|-------|
| `POST /pricing/calculate` | 60 requests / minute / client |
| `POST /cart/add` | 20 requests / minute / client |

Client identity: user ID → session ID → IP address.

---

## WooCommerce hardening

- Container product cannot be added without `kcp_config_uuid` + `kcp_price_hash` metadata
- KCP cart items require UUID, hash, and configuration JSON
- Invalid cart items removed automatically with customer notice
- All integrity failures logged via `SecurityLogger`

---

## Audit logging

Events written to `kcp_configuration_history`:

| Action | Trigger |
|--------|---------|
| `created` | Configuration saved |
| `updated` | Configuration modified |
| `deleted` | Configuration removed |
| `cart_prepared` | Added to cart (pricing refreshed) |
| `ordered` | Checkout completed |

Security events logged to WooCommerce logger (source: `kitchen-configurator-pro`) or `error_log` when `WP_DEBUG` is enabled.

---

## SQL safety

All repository queries continue to use `$wpdb->prepare()` for dynamic values. Table names come from trusted `Helpers::table_name()` helpers only.

---

## Next phase

Phase 9 — Testing.
