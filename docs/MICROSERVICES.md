# Safi Microframework — Microservices Architecture Guide

This document outlines patterns and practices for deploying `safi` as a lightweight, cloud-native microservice.

---

## 1. Core Principles

When deploying Safi in a microservices environment, adhere to the following architecture rules:

- **Package Minimization:** Install only required packages. Stateless API services do not require `safi-view-twig` or session middleware.
- **Database Isolation:** Each service must own its database exclusively. Do not share RedBean connections or tables across service boundaries.
- **Stateless Authentication:** Use Bearer Tokens (JWT/Paseto) or API keys. Avoid cookie-based sessions.
- **12-Factor App Compliance:** Configuration via environment variables, unbuffered log streaming to `stdout`, and graceful handling of `SIGTERM`.

---

## 2. Observability & Distributed Tracing

### Context Propagation
Microservices must track requests across network boundaries. `safi-core` manages this using the `CorrelationIdMiddleware`.

1. **Inbound Tracking:** Reads incoming `X-Correlation-ID` or W3C `traceparent` HTTP headers. Generates a UUIDv4 if missing.
2. **Log Context:** Automatically binds the active Correlation ID to every logger record.
3. **Outbound Tracking:** Forwards the current Correlation ID in HTTP headers during service-to-service calls.

### Outbound HTTP Context Forwarding Example

```php
namespace Safi\Http;

class InternalServiceClient
{
    public function __construct(
        private readonly string $correlationId
    ) {}

    public function get(string $url): ResponseInterface
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Correlation-ID: ' . $this->correlationId,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return new Response($response);
    }
}
```

---

## 3. Health Probes & Container Runtime

Kubernetes and container orchestrators require distinct liveness and readiness endpoints.

### `/healthz/liveness`
Verifies that the PHP process and Kernel are responding. Always returns HTTP 200 unless the runtime is deadlocked.

### `/healthz/readiness`
Verifies underlying dependencies (database connection, APCu cache availability) before routing traffic.

```json
{
  "status": "UP",
  "checks": {
    "database": "OK",
    "apcu": "OK"
  },
  "timestamp": "2026-07-25T01:23:00Z"
}
```

---

## 4. Fault Tolerance & Resiliency

### Circuit Breaker Pattern
To prevent cascading failures when downstream services fail, use an APCu-backed Circuit Breaker.

- **CLOSED (Normal):** Requests pass through.
- **OPEN (Failing):** If 5 consecutive failures occur within 10 seconds, the breaker trips. Requests fail immediately (0ms) with a fallback response without making a network call.
- **HALF-OPEN (Recovery):** After a cooldown period (e.g., 30 seconds), a single probe request is allowed through. Success resets the state to `CLOSED`; failure resets it to `OPEN`.

```php
use Safi\Resilience\CircuitBreaker;

$breaker = new CircuitBreaker(serviceName: 'payment-service', threshold: 5, timeout: 30);

if (!$breaker->isAvailable()) {
    return new JsonResponse(['error' => 'Service temporarily unavailable'], 530);
}

try {
    $response = $paymentClient->charge($payload);
    $breaker->recordSuccess();
    return $response;
} catch (\Throwable $e) {
    $breaker->recordFailure();
    throw $e;
}
```

---

## 5. Structured NDJSON Logging

Logs are written directly to `php://stdout` in Newline-Delimited JSON (NDJSON) format. Log collectors (e.g., Vector, FluentBit) ingest these streams without regex parsing overhead.

```json
{"time":"2026-07-25T01:23:16Z","level":"ERROR","correlation_id":"a8f3c9e1-4b2a-412d","service":"inventory-service","message":"Stock update failed","context":{"sku":"ITEM-102"}}
```
