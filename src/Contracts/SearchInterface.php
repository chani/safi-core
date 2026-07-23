<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Contracts;

interface SearchInterface
{
    /**
     * Executes a full-text search query across indexed modules.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query): array;

    /**
     * Indexes a document entry for full-text search discovery.
     */
    public function index(string $module, string $title, string $content, string $url): void;

    /**
     * Clears indexed entries belonging to a specific module.
     */
    public function clearModuleIndex(string $module): void;
}
