# µADR-016: Mandatory Constructor Property Promotion and Early Return Guards
-----
tags: clean-code php8 kiss
status: accepted

## Context
Reducing property assignment boilerplate and conditional nesting depth.

## Decision
- Constructor Property Promotion must be used for dependency injection.
- Nested execution paths must be flattened using early return guards.

## Guardrail / Consequences
Manual property assignments ($this->a = $a) inside constructors are prohibited for standard injections.
