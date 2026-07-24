# µADR-006: Strict Middleware Response Assertion
-----
tags: middleware pipeline type-safety
status: accepted

## Context
Broken propagation calls inside custom middleware layers lead to empty HTTP responses.

## Decision
- MiddlewarePipeline asserts that downstream processing layers yield a valid Response instance.
- Returning null or invalid types triggers an immediate RuntimeException.

## Guardrail / Consequences
Middleware layers must either invoke $handler->handle() or return a concrete Response instance.
