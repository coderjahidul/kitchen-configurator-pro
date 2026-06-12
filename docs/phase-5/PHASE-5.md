# Phase 5 — REST API

> **Status:** ✅ Complete  
> **Namespace:** `kcp/v1`  
> **Base URL:** `/wp-json/kcp/v1`

---

## Deliverables

### Infrastructure

| File | Purpose |
|------|---------|
| `src/Api/ApiServiceProvider.php` | Route registration |
| `src/Api/ApiResponse.php` | Standardized response envelope |
| `src/Api/RestController.php` | Base controller helpers |
| `src/Security/RestAuth.php` | Authentication & permission checks |
| `src/Services/CatalogService.php` | Cached catalog reads |
| `src/Services/ConfigurationService.php` | Configuration CRUD + pricing |

### Controllers

| File | Endpoints |
|------|-----------|
| `src/Api/Controllers/CatalogController.php` | `GET /catalog` |
| `src/Api/Controllers/PricingController.php` | `POST /pricing/calculate` |
| `src/Api/Controllers/ConfigurationController.php` | Configuration CRUD |

---

## Response Format

### Success

```json
{
  "success": true,
  "data": { ... },
  "meta": { ... }
}
```

### Error

```json
{
  "success": false,
  "error": {
    "code": "kcp_validation_failed",
    "message": "Configuration validation failed.",
    "details": {
      "errors": ["Layout is required."]
    }
  }
}
```

---

## Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/catalog` | Public | Full active catalog (cached 1h) |
| POST | `/pricing/calculate` | Public | Server-side price calculation |
| GET | `/configurations` | Auth | List user's configurations |
| POST | `/configurations` | Auth | Create + save with pricing |
| GET | `/configurations/{uuid}` | Auth | Get single configuration |
| PUT | `/configurations/{uuid}` | Auth | Update + recalculate pricing |
| DELETE | `/configurations/{uuid}` | Auth | Delete configuration |

---

## Authentication

| Client | Required headers |
|--------|------------------|
| Logged-in user | `X-WP-Nonce: {wp_rest_nonce}` (cookie auth) |
| Guest | `X-KCP-Session-Id: {session_id}` |

On first `POST /configurations` for a guest, a new `session_id` is returned in `meta.session_id`. Store it and send on subsequent requests.

Administrators with `manage_kcp` bypass ownership checks.

---

## Example Requests

### GET Catalog

```bash
curl -s "https://example.com/wp-json/kcp/v1/catalog"
```

### POST Pricing Calculate

```bash
curl -s -X POST "https://example.com/wp-json/kcp/v1/pricing/calculate" \
  -H "Content-Type: application/json" \
  -d '{
    "layout_id": 1,
    "title": "Test Kitchen",
    "cabinets": [{
      "cabinet_id": 1,
      "material_id": 1,
      "color_id": 1,
      "handle_id": 1,
      "dimensions": { "width": 600, "height": 720, "depth": 560 },
      "accessories": []
    }],
    "global_options": {
      "worktop_id": 1,
      "worktop_length": 3000,
      "worktop_depth": 600
    }
  }'
```

### POST Configuration (guest)

```bash
curl -s -X POST "https://example.com/wp-json/kcp/v1/configurations" \
  -H "Content-Type: application/json" \
  -H "X-KCP-Session-Id: your-session-id" \
  -d '{ ... same payload ... }'
```

---

## Validation

All mutating endpoints sanitize input via `ConfigurationService`:

- Integer IDs via `absint`
- Text via `sanitize_text_field`
- Cabinet dimensions validated against catalog constraints
- Worktop/plinth dimensions validated against entity ranges
- Server-side pricing recalculated on create/update

---

## Next Phase

**Phase 6: Frontend Configurator** — ES6 SPA consuming these REST endpoints.
