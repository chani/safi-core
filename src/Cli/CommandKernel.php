<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Cli;

use Psr\Container\ContainerInterface;

final class CommandKernel
{
    /** @var array<string, CommandInterface> */
    private array $commands = [];

    public function __construct(private readonly ContainerInterface $container) {}

    public function registerCommand(CommandInterface $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    /**
     * Resolves and registers a command class string directly via DI container.
     *
     * @param class-string<CommandInterface> $commandClass
     */
    public function registerCommandClass(string $commandClass): void
    {
        /** @var CommandInterface $command */
        $command = $this->container->get($commandClass);
        $this->registerCommand($command);
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? null;

        if ($commandName === null || in_array($commandName, ['help', '--help'], true)) {
            $this->showHelp();
            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            fwrite(STDERR, "Error: Command '{$commandName}' not registered.\n");
            return 1;
        }

        $args = array_slice($argv, 2);

        return $this->commands[$commandName]->execute($args);
    }

    private function showHelp(): void
    {
        echo "Safi Core CLI Shell Engine\n";
        echo "Usage: php bin/safi [command] [options]\n\n";

        /** @var array<string, list<CommandInterface>> $grouped */
        $grouped = [];
        foreach ($this->commands as $command) {
            $grouped[$command->getCategory()][] = $command;
        }

        ksort($grouped);

        foreach ($grouped as $category => $commands) {
            echo " [" . strtoupper($category) . "]\n";
            foreach ($commands as $command) {
                printf("  %-25s %s\n", $command->getName(), $command->getDescription());
            }
            echo "\n";
        }
    }
}
