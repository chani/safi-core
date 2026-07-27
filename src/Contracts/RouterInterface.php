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
     * Phase 1: Matches HTTP request against registered routes and populates request attributes.
     */
    public function match(Request $request): Request;

    /**
     * Phase 2: Dispatches the matched route handler and returns an HTTP response.
     */
    public function dispatch(Request $request): Response;

    /**
     * Registers an HTTP route mapping.
     *
     * @param array<int|string, mixed>|callable|string $handler
     * @param array<string, mixed> $options
     */
    public function addRoute(string $method, string $path, mixed $handler, ?string $name = null, array $options = []): void;

    /**
     * Generates a relative URL for a named route via the underlying generator driver.
     *
     * @param array<string, mixed> $params
     */
    public function generateUrl(string $name, array $params = []): string;

    /**
     * Returns all currently registered route definitions for introspection.
     *
     * @return array<int, array{method: string, path: string, handler: mixed, name: string|null, options: array<string, mixed>}>
     */
    public function getRoutes(): array;
}
