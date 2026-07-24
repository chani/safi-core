# µADR-005: Stratified Non-Serialized Cache Engine
-----
tags: cache apcu json fallback
status: accepted

## Context
In-memory caching is lost across request lifecycles if APCu is unavailable on the host.

## Decision
- CacheService implements a fallback chain: APCu Shared RAM -> Local File Storage (JSON / PHP native array export).
- Database engines (SQLite) are removed from core caching to preserve kernel isolation.

## Guardrail / Consequences
Missing server extensions must not crash the application. Direct database operations for caching are prohibited.
