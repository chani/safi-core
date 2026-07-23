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

final readonly class JobQueueService
{
    public function __construct(
        private DatabaseDriverInterface $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function push(string $handlerClass, array $payload = []): void
    {
        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $createdAt = date('Y-m-d H:i:s');

        $this->db->exec(
            "INSERT INTO job (handler, payload, status, attempts, created_at) VALUES (?, ?, 'pending', 0, ?)",
            [$handlerClass, $jsonPayload, $createdAt],
        );

        $this->logger->info("Enqueued job handler: {$handlerClass}");
    }

    /**
     * @return array{id: int, handler: string, payload: string, attempts: int}|null
     */
    public function pop(): ?array
    {
        $rows = $this->db->query(
            "SELECT id, handler, payload, attempts FROM job WHERE status = 'pending' OR (status = 'failed' AND attempts < 3) ORDER BY id ASC LIMIT 1",
        );

        if ($rows === []) {
            return null;
        }

        $job = $rows[0];
        $rawId = $job['id'] ?? 0;
        $rawAttempts = $job['attempts'] ?? 0;

        $id = is_numeric($rawId) ? (int) $rawId : 0;
        $nextAttempts = (is_numeric($rawAttempts) ? (int) $rawAttempts : 0) + 1;

        $this->db->exec(
            "UPDATE job SET status = 'processing', attempts = ? WHERE id = ?",
            [$nextAttempts, $id],
        );

        return [
            'id' => $id,
            'handler' => is_string($job['handler'] ?? null) ? $job['handler'] : '',
            'payload' => is_string($job['payload'] ?? null) ? $job['payload'] : '',
            'attempts' => $nextAttempts,
        ];
    }

    public function complete(int $id): void
    {
        $this->db->exec("DELETE FROM job WHERE id = ?", [$id]);
    }

    public function fail(int $id, int $attempts): void
    {
        $status = ($attempts >= 3) ? 'buried' : 'failed';
        $this->db->exec("UPDATE job SET status = ? WHERE id = ?", [$status, $id]);
    }
}
