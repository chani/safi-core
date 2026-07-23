<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Cli\Commands;

use Psr\Container\ContainerInterface;
use Safi\Core\Cli\CommandInterface;
use Safi\Core\Services\JobQueueService;
use Throwable;

final readonly class WorkerCommand implements CommandInterface
{
    public function __construct(private ContainerInterface $container) {}

    #[\Override]
    public function getName(): string
    {
        return 'jobs:worker';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Starts the background job worker daemon.';
    }

    #[\Override]
    public function getCategory(): string
    {
        return 'jobs';
    }

    #[\Override]
    public function execute(array $args): int
    {
        fwrite(STDOUT, "Safi Task Worker Daemon active.\n");
        /** @var JobQueueService $queue */
        $queue = $this->container->get(JobQueueService::class);

        $maxJobs = isset($args[0]) ? (int) $args[0] : 0;
        $jobsProcessed = 0;

        while (true) {
            $job = $queue->pop();

            if ($job === null) {
                if ($maxJobs > 0 && $jobsProcessed >= $maxJobs) {
                    break;
                }
                sleep(2);
                continue;
            }

            $id = $job['id'];
            $handlerClass = $job['handler'];
            $attempts = $job['attempts'];

            try {
                if (!class_exists($handlerClass)) {
                    throw new \RuntimeException("Handler class '{$handlerClass}' not found.");
                }

                /** @var array<string, mixed> $payload */
                $payload = json_decode($job['payload'], true, 512, JSON_THROW_ON_ERROR);
                /** @var object $handlerInstance */
                $handlerInstance = $this->container->get($handlerClass);

                if (method_exists($handlerInstance, 'handle')) {
                    $handlerInstance->handle($payload);
                }

                $queue->complete($id);
            } catch (Throwable $e) {
                $queue->fail($id, $attempts);
            }

            $jobsProcessed++;
            if ($maxJobs > 0 && $jobsProcessed >= $maxJobs) {
                break;
            }
        }

        return 0;
    }
}
