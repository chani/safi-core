<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Http;

final readonly class CorrelationIdMiddleware implements MiddlewareInterface
{
    #[\Override]
    public function process(Context $context, RequestHandlerInterface $handler): Response
    {
        $correlationId = bin2hex(random_bytes(16));
        $context->request->setAttribute('correlation_id', $correlationId);

        $response = $handler->handle($context);
        $response->setHeader('X-Correlation-ID', $correlationId);

        return $response;
    }
}
