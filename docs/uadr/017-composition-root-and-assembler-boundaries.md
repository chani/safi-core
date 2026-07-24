# µADR-017: Composition Root and Assembler Boundaries
-----
tags: di composition-root solid
status: accepted

## Context
Restricting DI container access to prevent Service Locator anti-patterns.

## Decision
- The Assembler class is strictly bound to index.php, init.inc.php, and CLI entry points.

## Guardrail / Consequences
Type-hinting the Assembler or ContainerInterface inside application services or controllers is prohibited.
