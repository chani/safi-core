<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Contracts;

interface ModelInterface
{
    /**
     * Unwraps the underlying raw storage entity state container.
     */
    public function unwrap(): mixed;

    /**
     * Returns the unique persistence identifier for this entity instance.
     */
    public function getId(): int;
}
