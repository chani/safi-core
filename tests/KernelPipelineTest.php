<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Safi\Core\Contracts\RouterInterface;
use Safi\Core\Http\Request;
use Safi\Core\Http\Response;
use Safi\Core\Kernel;

final class KernelPipelineTest extends TestCase
{
    public function testKernelExecutesMiddlewarePipelineAndDispatchesResponse(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $router->method('match')->willReturnCallback(static fn(Request $r): Request => $r);
        $router->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Response('OK', 200));

        $kernel = new Kernel($container, $router, $logger);

        $request = new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $response = $kernel->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    public function testKernelCatchesUnhandledExceptionsAndReturns500(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $router->method('match')->willReturnCallback(static fn(Request $r): Request => $r);
        $router->method('dispatch')->willThrowException(new RuntimeException('Boom'));

        $kernel = new Kernel($container, $router, $logger);

        $request = new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/fail']);
        $response = $kernel->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('500 Internal Server Error', $response->getContent());
    }
}
