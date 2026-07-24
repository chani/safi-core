# µADR-009: Secure-by-Default Modular MVC Blueprint
-----
tags: mvc security architecture
status: accepted

## Context
Manual authorization checks in controllers lead to security flaws due to omitted checks.

## Decision
- All endpoints are locked by default.
- Routes require explicit 'public' => true configuration to allow unauthenticated access.
- Blocked route attempts generate a warning log entry detailing the intercepted path.

## Guardrail / Consequences
Unprotected endpoints are impossible without explicit public opt-in flags.
