<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Contracts;

interface ContainerRegistrarInterface
{
    /**
     * Binds a service factory or direct instance to a specific identifier.
     */
    public function set(string $id, callable | object $factory): void;

    /**
     * Configures the automatic interface-to-concrete implementation mapping array.
     *
     * @param array<string, string> $map
     */
    public function setInterfaceMap(array $map): void;
}
