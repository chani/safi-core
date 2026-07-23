<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Http;

use Psr\Container\ContainerInterface;
use RuntimeException;

final class MiddlewarePipeline implements RequestHandlerInterface
{
    /** @var array<int, string|callable|MiddlewareInterface> */
    private array $middlewares = [];
    private int $index = 0;

    /** @var callable(Context): Response */
    private $fallbackHandler;

    /**
     * @param callable(Context): Response $fallbackHandler
     */
    public function __construct(
        private readonly ContainerInterface $container,
        callable $fallbackHandler,
    ) {
        $this->fallbackHandler = $fallbackHandler;
    }

    public function add(string | callable | MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    #[\Override]
    public function handle(Context $context): Response
    {
        if (!isset($this->middlewares[$this->index])) {
            return ($this->fallbackHandler)($context);
        }

        $middleware = $this->middlewares[$this->index];
        $this->index++;

        if (is_string($middleware)) {
            /** @var MiddlewareInterface $middleware */
            $middleware = $this->container->get($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            $response = $middleware->process($context, $this);
        } elseif (is_callable($middleware)) {
            $response = $middleware($context, $this);
        } else {
            throw new RuntimeException('Invalid middleware type provided.');
        }

        if (!$response instanceof Response) {
            throw new RuntimeException('Middleware execution failed to return a valid Response instance.');
        }

        return $response;
    }
}
