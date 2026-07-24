# µADR-010: Enterprise DI Container Compiling
-----
tags: di caching rejected
status: rejected

## Context
Compiling the DI container into a flat static PHP array graph file.

## Decision
- Rejected. Cached interface maps provide sufficient performance.
- Compiling adds file I/O overhead without measurable runtime performance improvements.

## Guardrail / Consequences
Do not implement static container compilation build steps.
