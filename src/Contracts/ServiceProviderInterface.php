<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Contracts;

use Psr\Container\ContainerInterface;

interface ServiceProviderInterface
{
    /**
     * Phase 1: Register class factories and interface mappings into the write-capable registrar.
     */
    public function register(ContainerRegistrarInterface $registrar): void;

    /**
     * Phase 2: Execute post-assembly initialization logic using the read-only ContainerInterface.
     */
    public function boot(ContainerInterface $container): void;
}
