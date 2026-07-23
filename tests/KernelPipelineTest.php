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
use Safi\Core\Assembler;
use Safi\Core\Contracts\RouterInterface;
use Safi\Core\Http\Context;
use Safi\Core\Http\MiddlewareInterface;
use Safi\Core\Http\MiddlewarePipeline;
use Safi\Core\Http\Request;
use Safi\Core\Http\RequestHandlerInterface;
use Safi\Core\Http\Response;
use Safi\Core\Kernel;
use Safi\Core\Logger;

final class KernelPipelineTest extends TestCase
{
    public function testPipelineExecutesMiddlewareInOrder(): void
    {
        $logger = new Logger(false);
        $assembler = new Assembler($logger);

        $pipeline = new MiddlewarePipeline($assembler, function (Context $ctx): Response {
            $ctx->response->setContent($ctx->response->getContent() . 'Core');
            return $ctx->response;
        });

        $pipeline->add(new class implements MiddlewareInterface {
            public function process(Context $context, RequestHandlerInterface $handler): Response
            {
                $context->response->setContent('Header:');
                return $handler->handle($context);
            }
        });

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $response = new Response();
        $context = new Context($request, $response, $logger);

        $result = $pipeline->handle($context);
        $this->assertSame('Header:Core', $result->getContent());
    }

    public function testKernelCatchesUnhandledExceptionsAndReturns500(): void
    {
        $logger = new Logger(false);
        $assembler = new Assembler($logger);

        $router = $this->createMock(RouterInterface::class);
        $router->method('dispatch')->willThrowException(new \RuntimeException('Database crash'));

        $kernel = new Kernel($assembler, $router, $logger);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/crash']);

        $response = $kernel->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('500 Internal Server Error', $response->getContent());
    }
}
