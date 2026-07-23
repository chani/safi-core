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

final readonly class DiDumpCommand implements CommandInterface
{
    public function __construct(private ContainerInterface $container) {}

    #[\Override]
    public function getName(): string
    {
        return 'dev:di-dump';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Outputs active container wiring diagnostics.';
    }

    #[\Override]
    public function getCategory(): string
    {
        return 'dev';
    }

    #[\Override]
    public function execute(array $args): int
    {
        echo "=== Safi Core DI Container Wire Check ===\n";
        echo "Container instance type: " . $this->container::class . "\n";

        return 0;
    }
}
