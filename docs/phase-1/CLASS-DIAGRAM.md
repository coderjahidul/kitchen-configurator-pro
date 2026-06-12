# Kitchen Configurator Pro — Class Diagram

## Core Architecture Class Diagram

```mermaid
classDiagram
    direction TB

    class Plugin {
        -Container container
        +boot() void
        +activate() void
        +deactivate() void
    }

    class Container {
        -array bindings
        -array instances
        +bind(string id, callable factory) void
        +singleton(string id, callable factory) void
        +get(string id) mixed
        +has(string id) bool
    }

  Plugin --> Container

    class Activator {
        +activate()$ void
    }

    class Deactivator {
        +deactivate()$ void
    }

    Plugin --> Activator
    Plugin --> Deactivator
```

---

## Admin CRUD Layer (Phase 3 — Implemented)

```mermaid
classDiagram
    direction TB

    class AbstractCrudPage {
        <<abstract>>
        #Container container
        #array notices
        +render() void
        #handleList() void
        #handleForm() void
        #handleDelete() void
        #getRepository() RepositoryInterface
        #getColumns() array
        #getFields() array
    }

    class LayoutsPage
    class CabinetCategoriesPage
    class CabinetsPage
    class MaterialsPage
    class ColorsPage
    class HandlesPage
    class AccessoriesPage
    class PricingRulesPage
    class ConfigurationsPage
    class DashboardPage
    class SettingsPage

    AbstractCrudPage <|-- LayoutsPage
    AbstractCrudPage <|-- CabinetCategoriesPage
    AbstractCrudPage <|-- CabinetsPage
    AbstractCrudPage <|-- MaterialsPage
    AbstractCrudPage <|-- ColorsPage
    AbstractCrudPage <|-- HandlesPage
    AbstractCrudPage <|-- AccessoriesPage
    AbstractCrudPage <|-- PricingRulesPage
    AbstractCrudPage --> RepositoryInterface
```

---

## Service Provider Pattern

```mermaid
classDiagram
    direction LR

    class AdminServiceProvider {
        +register(Container c) void
        +boot() void
    }

    class ApiServiceProvider {
        +register(Container c) void
        +boot() void
    }

    class FrontendServiceProvider {
        +register(Container c) void
        +boot() void
    }

    class WooCommerceServiceProvider {
        +register(Container c) void
        +boot() void
    }

    class Plugin {
        +boot() void
    }

    Plugin --> AdminServiceProvider
    Plugin --> ApiServiceProvider
    Plugin --> FrontendServiceProvider
    Plugin --> WooCommerceServiceProvider
```

---

## Repository Layer

```mermaid
classDiagram
    direction TB

    class RepositoryInterface {
        <<interface>>
        +find(int id) Entity|null
        +findBySlug(string slug) Entity|null
        +findAll(array criteria) array
        +create(array data) Entity
        +update(int id, array data) Entity
        +delete(int id) bool
    }

    class AbstractRepository {
        #wpdb db
        #string table
        +find(int id) Entity|null
        +findAll(array criteria) array
        #mapRowToEntity(array row) Entity
        #prepareData(array data) array
    }

    class LayoutRepository
    class CabinetRepository
    class MaterialRepository
    class ColorRepository
    class HandleRepository
    class AccessoryRepository
    class PricingRuleRepository
    class ConfigurationRepository
    class ProjectRepository
    class ConfigurationHistoryRepository

    RepositoryInterface <|.. AbstractRepository
    AbstractRepository <|-- LayoutRepository
    AbstractRepository <|-- CabinetRepository
    AbstractRepository <|-- MaterialRepository
    AbstractRepository <|-- ColorRepository
    AbstractRepository <|-- HandleRepository
    AbstractRepository <|-- AccessoryRepository
    AbstractRepository <|-- PricingRuleRepository
    AbstractRepository <|-- ConfigurationRepository
    AbstractRepository <|-- ProjectRepository
    AbstractRepository <|-- ConfigurationHistoryRepository
```

---

## Domain Entities

```mermaid
classDiagram
    direction TB

    class Layout {
        +int id
        +string slug
        +string name
        +bool isActive
        +toArray() array
    }

    class Cabinet {
        +int id
        +int categoryId
        +string slug
        +Dimensions defaultDimensions
        +Dimensions minDimensions
        +Dimensions maxDimensions
        +Money basePrice
        +validateDimensions(Dimensions d) bool
    }

    class Material {
        +int id
        +MaterialType type
        +Money priceModifier
        +float priceMultiplier
    }

    class Color {
        +int id
        +int materialId
        +string hexCode
        +Money priceModifier
    }

    class Handle {
        +int id
        +Money price
    }

    class Accessory {
        +int id
        +Money price
        +bool isPerCabinet
    }

    class PricingRule {
        +int id
        +PricingRuleType ruleType
        +array conditions
        +array calculation
        +int priority
    }

    class Configuration {
        +int id
        +Uuid uuid
        +int layoutId
        +array configurationData
        +PricingSnapshot pricingSnapshot
        +ConfigurationStatus status
        +PriceHash priceHash
    }

    class Project {
        +int id
        +Uuid uuid
        +int userId
        +string name
    }

    Cabinet --> Dimensions
    Configuration --> Uuid
    Configuration --> PriceHash
    Configuration --> PricingSnapshot
```

---

## Value Objects

```mermaid
classDiagram
    direction LR

    class Dimensions {
        +int width
        +int height
        +int depth
        +isWithinRange(Dimensions min, Dimensions max) bool
        +surfaceArea() float
        +equals(Dimensions other) bool
    }

    class Money {
        +string amount
        +string currency
        +add(Money other) Money
        +multiply(float factor) Money
        +format() string
    }

    class Uuid {
        +string value
        +generate()$ Uuid
        +equals(Uuid other) bool
    }

    class PriceHash {
        +string hash
        +generate(PricingSnapshot snapshot) PriceHash
        +verify(PricingSnapshot snapshot) bool
    }

    class Position {
        +float x
        +float y
        +int rotation
    }
```

---

## Service Layer

```mermaid
classDiagram
    direction TB

    class CatalogService {
        -LayoutRepository layouts
        -CabinetRepository cabinets
        -MaterialRepository materials
        -ColorRepository colors
        -HandleRepository handles
        -AccessoryRepository accessories
        -CacheService cache
        +getFullCatalog() CatalogResponse
        +invalidateCache() void
    }

    class ConfigurationService {
        -ConfigurationRepository configs
        -ConfigurationHistoryRepository history
        -ValidationService validator
        -PricingEngine pricing
        +create(ConfigurationInput input) Configuration
        +update(Uuid uuid, ConfigurationInput input) Configuration
        +find(Uuid uuid) Configuration
        +lockForCart(Uuid uuid) Configuration
    }

    class ProjectService {
        -ProjectRepository projects
        -ConfigurationRepository configs
        +create(int userId, string name) Project
        +getHistory(Uuid projectUuid) array
    }

    class ValidationService {
        +validateConfiguration(array data) void
        +validateDimensions(Cabinet cabinet, Dimensions d) void
    }

    class QuoteService {
        -ConfigurationRepository configs
        -PdfGeneratorInterface pdf
        +generateQuote(Uuid uuid) string
    }

    ConfigurationService --> PricingEngine
    ConfigurationService --> ValidationService
    CatalogService --> CacheService
```

---

## Pricing Engine

```mermaid
classDiagram
    direction TB

    class PricingCalculatorInterface {
        <<interface>>
        +calculate(CalculationContext ctx) LineItem[]
        +priority() int
    }

    class PricingEngine {
        -array calculators
        -PricingRuleRepository rules
        -PriceHashGenerator hashGenerator
        +calculate(ConfigurationInput input) PricingSnapshot
        -buildContext(ConfigurationInput input) CalculationContext
    }

    class CalculationContext {
        +ConfigurationInput configuration
        +array cabinets
        +array materials
        +array lineItems
        +Money runningTotal
    }

    class BasePriceCalculator {
        +calculate(CalculationContext ctx) LineItem[]
        +priority() int
    }

    class DimensionCalculator {
        +calculate(CalculationContext ctx) LineItem[]
        +priority() int
    }

    class MaterialCalculator {
        +calculate(CalculationContext ctx) LineItem[]
        +priority() int
    }

    class HandleCalculator {
        +calculate(CalculationContext ctx) LineItem[]
        +priority() int
    }

    class AccessoryCalculator {
        +calculate(CalculationContext ctx) LineItem[]
        +priority() int
    }

    class RuleEngineCalculator {
        -PricingRuleRepository rules
        +calculate(CalculationContext ctx) LineItem[]
        +priority() int
    }

    class PriceHashGenerator {
        +generate(PricingSnapshot snapshot) PriceHash
    }

    PricingCalculatorInterface <|.. BasePriceCalculator
    PricingCalculatorInterface <|.. DimensionCalculator
    PricingCalculatorInterface <|.. MaterialCalculator
    PricingCalculatorInterface <|.. HandleCalculator
    PricingCalculatorInterface <|.. AccessoryCalculator
    PricingCalculatorInterface <|.. RuleEngineCalculator

    PricingEngine --> PricingCalculatorInterface
    PricingEngine --> PriceHashGenerator
    PricingEngine --> CalculationContext
```

---

## REST API Layer

```mermaid
classDiagram
    direction TB

    class RestController {
        <<abstract>>
        #string namespace
        #string restBase
        +registerRoutes() void
        #checkPermission(WP_REST_Request req) bool
        #success(mixed data, int status) WP_REST_Response
        #error(string code, string message, int status) WP_Error
    }

    class CatalogController {
        +getCatalog(WP_REST_Request req) WP_REST_Response
    }

    class ConfigurationController {
        +create(WP_REST_Request req) WP_REST_Response
        +get(WP_REST_Request req) WP_REST_Response
        +update(WP_REST_Request req) WP_REST_Response
        +delete(WP_REST_Request req) WP_REST_Response
    }

    class PricingController {
        +calculate(WP_REST_Request req) WP_REST_Response
    }

    class ProjectController {
        +list(WP_REST_Request req) WP_REST_Response
        +get(WP_REST_Request req) WP_REST_Response
    }

    class CartController {
        +add(WP_REST_Request req) WP_REST_Response
    }

    class QuoteController {
        +generate(WP_REST_Request req) WP_REST_Response
    }

    RestController <|-- CatalogController
    RestController <|-- ConfigurationController
    RestController <|-- PricingController
    RestController <|-- ProjectController
    RestController <|-- CartController
    RestController <|-- QuoteController

    CatalogController --> CatalogService
    ConfigurationController --> ConfigurationService
    PricingController --> PricingEngine
    ProjectController --> ProjectService
    CartController --> CartHandler
    QuoteController --> QuoteService
```

---

## WooCommerce Integration

```mermaid
classDiagram
    direction TB

    class ProductManager {
        +ensureContainerProduct() int
        +getProductId() int|null
    }

    class CartHandler {
        -ConfigurationService configs
        -PricingEngine pricing
        +addToCart(Uuid uuid) string
        +filterCartItemData(array data, int productId) array
        +setCartItemPrice(WC_Cart cart) void
    }

    class CheckoutHandler {
        +validateCheckout() void
        +verifyPriceIntegrity(WC_Cart cart) bool
    }

    class OrderHandler {
        +persistOrderMeta(WC_Order_Item item, array values) void
        +updateConfigurationStatus(int orderId) void
    }

    class OrderMetaDisplay {
        +displayAdminOrderMeta(WC_Order order) void
        +displayEmailOrderMeta(WC_Order order) void
    }

    CartHandler --> ConfigurationService
    CartHandler --> PricingEngine
    CheckoutHandler --> CartHandler
    OrderHandler --> ConfigurationService
```

---

## Database Migration System

```mermaid
classDiagram
    direction LR

    class MigrationInterface {
        <<interface>>
        +up()$ void
        +down()$ void
        +version()$ string
    }

    class MigrationRunner {
        -wpdb db
        -array migrations
        +run() void
        +rollback(string version) void
        +getCurrentVersion() string
    }

    class Migration_1_0_0 {
        +up()$ void
        +down()$ void
        +version()$ string
    }

    MigrationInterface <|.. Migration_1_0_0
    MigrationRunner --> MigrationInterface
```

---

## DTOs and Enums

```mermaid
classDiagram
    direction TB

    class ConfigurationInput {
        +int layoutId
        +string title
        +array cabinets
        +array globalOptions
    }

    class PricingSnapshot {
        +DateTime calculatedAt
        +string currency
        +LineItem[] lineItems
        +Money subtotal
        +Money tax
        +Money total
        +PriceHash priceHash
        +toArray() array
    }

    class LineItem {
        +string type
        +int referenceId
        +string label
        +int quantity
        +Money unitPrice
        +Money subtotal
        +array breakdown
    }

    class CatalogResponse {
        +array layouts
        +array cabinets
        +array materials
        +array colors
        +array handles
        +array accessories
    }

    class ConfigurationStatus {
        <<enumeration>>
        DRAFT
        SAVED
        QUOTED
        ORDERED
        ARCHIVED
    }

    class MaterialType {
        <<enumeration>>
        FRONT
        CARCASS
        WORKTOP
        PLINTH
    }

    class PricingRuleType {
        <<enumeration>>
        SURCHARGE
        DISCOUNT
        MULTIPLIER
        FIXED
    }

    PricingSnapshot --> LineItem
    PricingSnapshot --> PriceHash
```

---

## Dependency Flow (SOLID)

```mermaid
flowchart TB
    subgraph Presentation
        API[REST Controllers]
        ADMIN[Admin Pages]
        FE[Frontend Shortcode]
    end

    subgraph Application
        SVC[Services]
        PE[PricingEngine]
    end

    subgraph Domain
        ENT[Entities / DTOs / VOs]
        CTR[Contracts / Interfaces]
    end

    subgraph Infrastructure
        REPO[Repositories]
        WC[WooCommerce Handlers]
        DB[(MySQL)]
    end

    API --> SVC
    ADMIN --> SVC
    FE --> API

    SVC --> CTR
    SVC --> ENT
    PE --> CTR

    REPO ..|> CTR
    REPO --> DB
    WC --> SVC
    SVC --> REPO
```

**SOLID mapping:**

| Principle | Implementation |
|-----------|----------------|
| **S** — Single Responsibility | Each calculator handles one price aspect; repositories only persist |
| **O** — Open/Closed | New calculators implement `PricingCalculatorInterface` without modifying engine |
| **L** — Liskov Substitution | All repositories honor `RepositoryInterface` |
| **I** — Interface Segregation | Separate interfaces: Repository, PricingCalculator, Migration, PdfGenerator |
| **D** — Dependency Inversion | Services depend on interfaces; container wires concrete implementations |

---

## REST API Endpoint Map (Reference for Phase 5)

| Method | Endpoint | Controller | Service |
|--------|----------|------------|---------|
| GET | `/kcp/v1/catalog` | CatalogController | CatalogService |
| POST | `/kcp/v1/configurations` | ConfigurationController | ConfigurationService |
| GET | `/kcp/v1/configurations/{uuid}` | ConfigurationController | ConfigurationService |
| PUT | `/kcp/v1/configurations/{uuid}` | ConfigurationController | ConfigurationService |
| DELETE | `/kcp/v1/configurations/{uuid}` | ConfigurationController | ConfigurationService |
| POST | `/kcp/v1/pricing/calculate` | PricingController | PricingEngine |
| GET | `/kcp/v1/projects` | ProjectController | ProjectService |
| GET | `/kcp/v1/projects/{uuid}` | ProjectController | ProjectService |
| POST | `/kcp/v1/cart/add` | CartController | CartHandler |
| POST | `/kcp/v1/quotes/{uuid}` | QuoteController | QuoteService |

---

*End of Class Diagram Document*
