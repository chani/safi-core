# µADR-025: Model Inference Exception Mapping
-----
tags: controller database validation
status: accepted

## Context
Redundant entity check-if-exists logic repeated across controller actions.

## Decision
- AbstractController provides findModelOrFail(Class, id).
- Automatically throws a ValidationException if the entity does not exist.

## Guardrail / Consequences
Manual existence verification blocks (e.g. if (!$model) throw ...) in controllers are prohibited.
