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
use Safi\Core\Models\Job;

final readonly class JobQueueService
{
    public function __construct(
        private DatabaseDriverInterface $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function push(string $handlerClass, array$payload = []): void
    {
        $job = $this->db->dispenseModel(Job::class);
        if ($job instanceof Job) {
            $job->handler = $handlerClass;
            $job->payload = json_encode($payload, JSON_THROW_ON_ERROR);
            $job->status = 'pending';
            $job->attempts = 0;
            $job->createdAt = date('Y-m-d H:i:s');

            $this->db->storeModel($job);
        }

        $this->logger->info("Enqueued job handler: {$handlerClass}");
    }

    /**
     * @return array{id: int, handler: string, payload: string, attempts: int}|null
     */
    public function pop(): ?array
    {
        return $this->db->transaction(function (DatabaseDriverInterface$db): ?array {
            /** @var list<Job> $jobs */
            $jobs = $db->findModels(Job::class, "(status = 'pending' OR (status = 'failed' AND attempts < 3)) ORDER BY id ASC LIMIT 1");

            if ($jobs === []) {
                return null;
            }

            $job = $jobs[0];
            $nextAttempts = $job->attempts + 1;

            $job->attempts = $nextAttempts;
            $job->status = 'processing';
            $db->storeModel($job);

            return [
                'id' => $job->getId(),
                'handler' => $job->handler,
                'payload' => $job->payload,
                'attempts' => $nextAttempts,
            ];
        });
    }

    public function complete(int $id): void
    {
        $job = $this->db->loadModel(Job::class, $id);
        if ($job->getId() > 0) {
            $this->db->trashModel($job);
        }
    }

    public function fail(int $id, int$attempts): void
    {
        $job = $this->db->loadModel(Job::class, $id);
        if ($job->getId() > 0) {
            $job->status = ($attempts >= 3) ? 'buried' : 'failed';
            $this->db->storeModel($job);
        }
    }
}
