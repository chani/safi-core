# Safi Core (`safi-core`)

Core package for the Safi Microframework (PHP 8.5+). Built around pure constructor injection, explicit composition root wiring, an HTTP context pipeline, attribute routing, and driver contracts.

---

## Architecture Principles

### 1. Pure Constructor Injection (`Assembler`)
* **No Service Locators:** Controllers and services never pull from a global container. All dependencies are explicitly declared in constructors via PHP 8.5 property promotion.
* **Composition Root:** The `Assembler` is used exclusively during application startup (`init.inc.php` or CLI entry points) to wire components together deterministically.

### 2. HTTP Pipeline & Context (`Context`)
* Combines `Request`, `Response`, and `LoggerInterface` into a strict `Context` object flowing through the `MiddlewarePipeline`.
* Eliminates messy parameter lists and keeps infrastructure concerns out of domain logic.

### 3. Attribute Routing (`#[Route]`)
* Routes are defined via PHP attributes directly on controller methods.
* **Default Lockdown:** All endpoints require authentication (`401 Unauthorized`) unless explicitly configured as `public: true`.

---

## Package Layout

```text
src/
├── Attributes/       # #[Route] definition
├── Cli/              # Command interface & kernel
│   └── Commands/     # Core maintenance tasks
├── Contracts/        # Interfaces (Router, View, Database, Search)
├── Event/            # Synchronous event dispatcher
├── Exception/        # Core exceptions
├── Http/             # Request, Response, Context, Pipeline
├── Services/         # Cache, JobQueue, Security
└── Util/             # Token-based class finder
```

---

## License

Distributed under the **MIT License**. Author: **Jean Bruenn**
