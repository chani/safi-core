<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Contracts;

use Safi\Core\Http\Request;
use Safi\Core\Http\Response;

interface RouterInterface
{
    /**
     * Registers an HTTP route mapping.
     *
     * @param array<int|string, mixed>|callable|string $handler
     * @param array<string, mixed> $options
     */
    public function addRoute(string $method, string $path, mixed $handler, ?string $name = null, array $options = []): void;

    /**
     * Dispatches an incoming HTTP request through the router execution chain.
     */
    public function dispatch(Request $request): Response;

    /**
     * Generates a relative URL for a named route via the underlying generator driver.
     *
     * @param array<string, mixed> $params
     */
    public function generateUrl(string $name, array $params = []): string;
}
