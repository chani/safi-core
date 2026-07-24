<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Safi\Core\Contracts\RouterInterface;
use Safi\Core\Http\Context;
use Safi\Core\Http\MiddlewareInterface;
use Safi\Core\Http\MiddlewarePipeline;
use Safi\Core\Http\Request;
use Safi\Core\Http\Response;
use Throwable;

final class Kernel
{
    public const string VERSION = '0.1.3';

    /** @var array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> */
    private array $middlewares = [];

    /**
     * @param array<int, class-string<MiddlewareInterface>|callable|MiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        array $middlewares = [],
    ) {
        $this->middlewares = $middlewares;
    }

    public function handle(Request $request): Response
    {
        $response = new Response();
        $context = new Context($request, $response, $this->logger);

        try {
            $pipeline = new MiddlewarePipeline(
                $this->container,
                fn(Context $ctx): Response => $this->router->dispatch($ctx->request),
            );

            foreach ($this->middlewares as $middleware) {
                $pipeline->add($middleware);
            }

            return $pipeline->handle($context);
        } catch (Throwable $e) {
            $this->logger->error('Unhandled kernel exception: ' . $e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($request->isXhr()) {
                return new Response(
                    (string) json_encode(['error' => 'Internal Server Error']),
                    500,
                    ['Content-Type' => 'application/json'],
                );
            }

            return new Response(
                '<h1>500 Internal Server Error</h1><p>An unexpected error occurred.</p>',
                500,
                ['Content-Type' => 'text/html; charset=utf-8'],
            );
        }
    }
}
