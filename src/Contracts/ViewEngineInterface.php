<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Contracts;

interface ViewEngineInterface
{
    /**
     * Renders a template file with context data.
     *
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string;

    /**
     * Registers an isolated view namespace for component templates (e.g. @Incursio/index.twig).
     */
    public function registerNamespace(string $namespace, string $path): void;

    /**
     * Injects a global variable available across all rendered view contexts.
     */
    public function addGlobal(string $name, mixed $value): void;
}
