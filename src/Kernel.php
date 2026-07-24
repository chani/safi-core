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
use Safi\Core\Contracts\ViewEngineInterface;
use Safi\Core\Exception\ValidationException;
use Safi\Core\Http\Context;
use Safi\Core\Http\MiddlewareInterface;
use Safi\Core\Http\MiddlewarePipeline;
use Safi\Core\Http\Request;
use Safi\Core\Http\Response;
use Throwable;

final class Kernel
{
    public const string VERSION = '0.1.7';

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
        } catch (ValidationException $e) {
            $this->logger->warning('Security / Validation boundary triggered: ' . $e->getMessage());

            if ($request->isXhr()) {
                return new Response(
                    (string) json_encode(['error' => 'Forbidden', 'message' => $e->getMessage()]),
                    403,
                    ['Content-Type' => 'application/json'],
                );
            }

            return $this->renderErrorResponse(403, '403 Forbidden', $e->getMessage());
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

            return $this->renderErrorResponse(500, '500 Internal Server Error', 'An unexpected system exception occurred.');
        }
    }

    private function renderErrorResponse(int $code, string $title, string $message): Response
    {
        if ($this->container->has(ViewEngineInterface::class)) {
            try {
                /** @var ViewEngineInterface $view */
                $view = $this->container->get(ViewEngineInterface::class);
                $html = $view->render('errors/error.twig', [
                    'code' => $code,
                    'title' => $title,
                    'message' => $message,
                ]);

                return new Response($html, $code, ['Content-Type' => 'text/html; charset=utf-8']);
            } catch (Throwable) {
                // Fallback to minimal response if view rendering fails
            }
        }

        $fallback = sprintf(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>%d %s</title></head><body style="font-family:sans-serif;background:#1e1f22;color:#bcbec4;padding:3rem;text-align:center;"><h1 style="color:#ff6b7b;">%d %s</h1><p>%s</p></body></html>',
            $code,
            htmlspecialchars($title),
            $code,
            htmlspecialchars($title),
            htmlspecialchars($message),
        );

        return new Response($fallback, $code, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
