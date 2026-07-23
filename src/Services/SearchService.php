<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Services;

use Psr\Log\LoggerInterface;
use Safi\Core\Contracts\DatabaseDriverInterface;

final readonly class SearchService
{
    public function __construct(
        private DatabaseDriverInterface $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query): array
    {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        $this->logger->info("Executing search query: '{$query}'");
        $sql = "SELECT * FROM search_index WHERE search_index MATCH ? ORDER BY rank LIMIT 20";

        return $this->db->query($sql, [$query . '*']);
    }

    public function index(string $module, string $title, string $content, string $url): void
    {
        $this->db->exec("DELETE FROM search_index WHERE url = ?", [$url]);
        $sql = "INSERT INTO search_index (module, title, content, url) VALUES (?, ?, ?, ?)";
        $this->db->exec($sql, [$module, $title, strip_tags($content), $url]);
    }

    public function clearModuleIndex(string $module): void
    {
        $this->db->exec("DELETE FROM search_index WHERE module = ?", [$module]);
    }
}
