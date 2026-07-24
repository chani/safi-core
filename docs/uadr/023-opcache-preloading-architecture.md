# µADR-023: OPcache Preloading Architecture
-----
tags: performance opcache preloading
status: accepted

## Context
Class parsing on every request adds compilation latency.

## Decision
- Provide an optional root preload.php script to compile framework classes into shared memory at server startup.

## Guardrail / Consequences
Preload scripts must remain optional and must not hardcode local environment parameters.
