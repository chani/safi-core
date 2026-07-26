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

## Built-in Framework APM & Core Telemetry

### Asynchronous Deferred I/O & Sampling Engine
- [ ] Implement an in-memory ring buffer/span collector during request lifecycle.
- [ ] Flush telemetry data post-response using `fastcgi_finish_request()` to eliminate blocking I/O overhead.
- [ ] Build a runtime Sampling Engine (e.g., sample 10% of 2xx requests, 100% of 4xx/5xx or slow requests).
- [ ] Implement unbuffered NDJSON stdout Logger (12-Factor app compliance).

### High-Resolution Micro-Profiling & Spans
- [ ] Implement high-precision timer service using `hrtime(true)`.
- [ ] Collect hierarchical transaction "spans" with start/end times (`kernel.boot`, `router.match`, `middleware.process`).
- [ ] Track memory deltas (`memory_get_peak_usage()`) at key lifecycle milestones to identify component bloat.

### Enhanced Exception & Context Tracing
- [ ] Capture unhandled exceptions with full call-stack context, memory peak, and request attributes.
- [ ] Track event listener execution counts and individual listener execution durations in `EventDispatcher`.
- [ ] Monitor incoming security headers, CSRF validation latency, and client IP resolution status.
