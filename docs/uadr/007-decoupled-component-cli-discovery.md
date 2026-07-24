# µADR-007: Decoupled Component CLI Discovery
-----
tags: cli commands modular
status: accepted

## Context
CLI commands were historically restricted to static framework core directories.

## Decision
- CommandKernel scans loaded components dynamically for matching Cli/*Command.php files.
- Discovered commands are registered in the main CLI runner automatically.

## Guardrail / Consequences
Components must declare CLI commands in their isolated module directories.
