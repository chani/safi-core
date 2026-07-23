<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Cli;

interface CommandInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getCategory(): string;

    /**
     * @param list<string> $args
     */
    public function execute(array $args): int;
}
