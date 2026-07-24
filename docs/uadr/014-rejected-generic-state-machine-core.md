# µADR-014: Generic State Machine Engine in Core
-----
tags: architecture state-machine rejected
status: rejected

## Context
Integrating a global State Machine engine into core to track HTTP lifecycles.

## Decision
- Rejected. HTTP is inherently stateless. A global state machine adds unnecessary complexity.
- State tracking is restricted to background job queues.

## Guardrail / Consequences
Do not introduce state machine layers into the synchronous HTTP execution path.
