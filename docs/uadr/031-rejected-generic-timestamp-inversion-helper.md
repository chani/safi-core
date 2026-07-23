# µADR-031: Rejected Generic Timestamp Inversion Helper in Core
-----
tags: #domain #models #property-hooks #time #architecture
status: rejected
context: Consideration of introducing a centralized kernel service to calculate state expirations and time-based inversions from database timestamps.
reason:
  - Calculating state transitions from time deltas (e.g. lock expirations) is application business logic, not framework infrastructure.
  - Timestamp formats vary across engines (Unix integers, ISO strings, DATETIME), making generic core helpers brittle.
accepted_alternative:
  - Time-based state checks are encapsulated within domain models using PHP 8.5 Property Hooks (e.g. `$lock->isExpired`).
consequences:
  - Strictly enforces the boundary between system infrastructure and application domain logic.
