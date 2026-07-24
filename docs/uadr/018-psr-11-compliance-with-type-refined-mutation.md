# µADR-018: PSR-11 Compliance with Type-Refined Mutation
-----
tags: di psr11 interfaces
status: accepted

## Context
PSR-11 ContainerInterface is read-only, but components must register factories during boot.

## Decision
- Infrastructure uses read-only ContainerInterface type-hints.
- Extension components must execute type checks (instanceof Assembler) before invoking mutation methods.

## Guardrail / Consequences
Container mutation operations are allowed only after explicit type refinement verification.
