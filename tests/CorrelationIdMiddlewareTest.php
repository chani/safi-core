<?php

declare(strict_types=1);

namespace Safi\Core\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Safi\Core\Http\Context;
use Safi\Core\Http\CorrelationIdMiddleware;
use Safi\Core\Http\Request;
use Safi\Core\Http\RequestHandlerInterface;
use Safi\Core\Http\Response;

final class CorrelationIdMiddlewareTest extends TestCase
{
    public function testAttachesCorrelationIdAttributeAndHeader(): void
    {
        $middleware = new CorrelationIdMiddleware();
        $request = new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $response = new Response();
        $context = new Context($request, $response, new NullLogger());

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response('OK', 200));

        $res = $middleware->process($context, $handler);

        $this->assertNotEmpty($request->getAttribute('correlation_id'));
        $this->assertArrayHasKey('X-Correlation-ID', $res->getHeaders());
    }
}
