<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core;

use Psr\Log\LoggerInterface;
use Safi\Core\Contracts\RouterInterface;
use Safi\Core\Contracts\ViewEngineInterface;
use Safi\Core\Exception\ForbiddenException;
use Safi\Core\Exception\NotFoundException;
use Safi\Core\Exception\ValidationException;
use Safi\Core\Http\Context;
use Safi\Core\Http\MiddlewareInterface;
use Safi\Core\Http\MiddlewarePipeline;
use Safi\Core\Http\Request;
use Safi\Core\Http\Response;
use Throwable;

final class Kernel
{
    public const string VERSION = '0.1.16';

    /** @var array<int, callable|MiddlewareInterface> */
    private array $middlewares;

    /**
     * @param array<int, callable|MiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly ?ViewEngineInterface $view = null,
        array $middlewares = [],
    ) {
        $this->middlewares = $middlewares;
    }

    /**
     * @return array<int, callable|MiddlewareInterface>
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function handle(Request $request): Response
    {
        $request = $this->router->match($request);

        $response = new Response();
        $context = new Context($request, $response, $this->logger);

        try {
            $pipeline = new MiddlewarePipeline(
                fn(Context $ctx): Response => $this->router->dispatch($ctx->request),
            );

            foreach ($this->middlewares as $middleware) {
                $pipeline->add($middleware);
            }

            return $pipeline->handle($context);
        } catch (NotFoundException $e) {
            $this->logger->warning('Resource not found: ' . $e->getMessage());
            return $this->buildErrorResponse($request, 404, '404 Not Found', $e->getMessage());
        } catch (ForbiddenException $e) {
            $this->logger->warning('Access forbidden: ' . $e->getMessage());
            return $this->buildErrorResponse($request, 403, '403 Forbidden', $e->getMessage());
        } catch (ValidationException $e) {
            $this->logger->warning('Validation failure boundary: ' . $e->getMessage());
            return $this->buildErrorResponse($request, 400, '400 Bad Request', $e->getMessage());
        } catch (Throwable $e) {
            $this->logger->error('Unhandled kernel exception: ' . $e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->buildErrorResponse($request, 500, '500 Internal Server Error', $e->getMessage());
        }
    }

    private function buildErrorResponse(Request $request, int $code, string $title, string $message): Response
    {
        if ($request->isXhr()) {
            return new Response(
                (string) json_encode(['error' => $title, 'message' => $message]),
                $code,
                ['Content-Type' => 'application/json'],
            );
        }

        $isAdmin = str_starts_with($request->getUri(), '/admin');

        if ($this->view instanceof ViewEngineInterface) {
            try {
                $template = $isAdmin ? 'errors/admin_error' : 'errors/error';
                $html = $this->view->render($template, [
                    'code' => $code,
                    'title' => $title,
                    'message' => $message,
                ]);

                return new Response($html, $code, ['Content-Type' => 'text/html; charset=utf-8']);
            } catch (Throwable) {
                // Fallback
            }
        }

        return new Response("<h1>{$code} {$title}</h1><p>{$message}</p>", $code, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
