# µADR-001: Pure Reflection DI & Ambiguous Interface Guard
-----
tags: di container reflection autowiring
status: accepted

## Context
Dependencies must be resolved without Service Locators or static facades.

## Decision
- The Assembler autowires dependencies via constructor reflection.
- Concrete classes are mapped automatically to matching interfaces during component discovery.
- If multiple concrete implementations are discovered for an interface, the Assembler throws an AmbiguousInterfaceException during boot.

## Guardrail / Consequences
Passing or injecting the DI container into controllers, services, or models is strictly prohibited. Multi-implementation interfaces require explicit factory binding.
