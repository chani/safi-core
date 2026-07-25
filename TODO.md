# safi-core — TODO

## Core Refactorings

- [ ] Add Correlation-ID to Logger context in `CorrelationIdMiddleware`.
- [ ] Add Security Headers Middleware and generate per-request CSP nonce.
- [ ] Add IPv4/IPv6 anonymization utility (`Anonymizer::ip()`).
- [ ] Support PHP 8.5 union types in `Assembler.php` autowiring.
- [ ] Replace generic `\RuntimeException` with domain-specific exceptions.

## Telemetry Contracts

- [ ] Define `CacheStatProviderInterface`.
- [ ] Implement `SystemStatProvider` for OPcache, JIT, system load, and memory usage.

## Specialized Features

- [ ] Implement APCu in-memory Bloom Filter to intercept negative database lookups.
- [ ] Implement XFetch probabilistic early expiration in `ApcuCache` (stampede protection).
- [ ] Implement APCu Circuit Breaker (`CLOSED`, `OPEN`, `HALF-OPEN`) for external calls.
- [ ] Implement unbuffered NDJSON stdout Logger (12-Factor app compliance).
