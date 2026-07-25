# Safi Auth — Permission & Delegation System

This document describes the design and implementation of the authorization system in `safi-auth`.

---

## 1. Overview & Core Principles

The authorization model is built around attribute-driven discovery and hierarchical group delegation.

- **Attribute-Driven Definition:** Permission metadata is declared directly on controller methods using PHP 8.5 attributes.
- **Auto-Discovery:** Available permissions are discovered via Reflection during route registration/caching, eliminating manual database seeding for permission keys.
- **Delegation & Visibility Control:** Administrators can only assign permissions they personally hold (`is_visible`). Permission assignment is distinct from permission delegation (`can_delegate`).
- **In-Memory Verification:** Active user permissions are stored in the session/APCu context and verified using constant-time array lookups.

---

## 2. Declaring Permissions (`#[Permission]`)

Permissions are attached to controller routes using the `#[Permission]` attribute.

```php
namespace App\Components\Finance\Controllers;

use Safi\Auth\Attributes\Permission;
use Safi\Router\Attributes\Route;
use Safi\Http\Response;

class StockController
{
    #[Route('/finance/stocks/trade', method: 'POST')]
    #[Permission(
        key: 'finance.stocks.trade',
        label: 'Execute Stock Trades',
        category: 'Finance'
    )]
    public function trade(): Response
    {
        // Business logic
    }
}
```

### Explicit Keys vs. Automatic Inference

Auto-inferring permission keys from controller or method names (e.g., `FinanceController::trade` -> `finance.controller.trade`) is prohibited. Renaming a class or method would break persisted permission records in the database. Explicit keys (e.g., `finance.stocks.trade`) decouple domain authorization identifiers from code structure.

---

## 3. Discovery Lifecycle

1. **Scan:** When building or warming the route cache, `safi-auth` scans registered controllers via Reflection.
2. **Register:** Extracted `#[Permission]` attributes are stored in an in-memory registry (APCu/cache).
3. **Expose:** The permission administration interface reads from this registry to display configurable options without requiring database migrations or seeders.

---

## 4. Group Hierarchies, Delegation, and Visibility

Permissions are managed via groups with support for parent-child inheritance and delegated administration.

```
                  [ Admin Group ] 
                         │
                         ├─► Holds 'finance.stocks.trade'
                         └─► 'can_delegate' = true
                               │
                               ▼
                    [ Sub-Admin Group ]
                         │
                         ├─► Sees 'finance.stocks.trade' in Matrix
                         └─► 'can_delegate' = false
                               │
                               ▼
                         [ End-User ]
                               └─► Holds permission, cannot delegate
```

### Database Schema Concept (`group_permissions`)

- `group_id` (int): Target group.
- `permission_key` (string): Explicit domain permission key.
- `is_granted` (bool): Whether members of the group can perform the action.
- `can_delegate` (bool): Whether administrators in this group can grant this permission to sub-groups or users.

### Security Guards

1. **Visibility Guard (`is_visible`):** When an administrator views the permission management UI, the list is filtered to show only permissions held by that administrator. Permissions not held by the editor are hidden, preventing privilege escalation.
2. **Delegation Guard (`can_delegate`):** An administrator can only grant a permission to child entities if their own assignment has `can_delegate = true`.

---

## 5. Enforcement Mechanisms

### Route Middleware Enforcement
When a route contains a `#[Permission]` attribute, `AuthMiddleware` verifies the permission before controller execution. If authorization fails, an HTTP 403 (`ForbiddenException`) is thrown.

### Programmatic Check
Inside services or controllers, permissions can be checked directly:

```php
if (!$this->authService->can('finance.stocks.trade')) {
    throw new ForbiddenException("Required permission: finance.stocks.trade");
}
```

Permissions for the authenticated user are evaluated on login, resolved through group inheritance, and cached in memory
