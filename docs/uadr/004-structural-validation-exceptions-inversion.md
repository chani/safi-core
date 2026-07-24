# µADR-004: Structural Validation Exceptions Inversion
-----
tags: models validation exceptions http
status: accepted

## Context
Property Hook boundary violations throw validation exceptions, which risk uncaught HTTP 500 crashes.

## Decision
- Introduce a dedicated Safi\Core\Exception\ValidationException class.
- The kernel and middleware pipeline intercept ValidationException instances globally.
- Exceptions are automatically converted into HTTP 400 Bad Request responses (JSON for XHR, rendered HTML for standard requests).

## Guardrail / Consequences
Controllers must not contain try-catch blocks for standard model input validation.
