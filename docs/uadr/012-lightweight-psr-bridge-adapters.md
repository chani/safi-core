# µADR-012: PSR-7 / PSR-15 Compliance via Lightweight Bridge Adapters
-----
tags: http psr7 psr15 adapters
status: accepted

## Context
Direct implementation of PSR-7 interfaces introduces unnecessary class boilerplate.

## Decision
- Native Request and Response classes remain lightweight and native.
- Interoperability with third-party PSR packages is provided via bridge adapters.

## Guardrail / Consequences
Do not add PSR-7 interface boilerplate to primary execution path classes.
