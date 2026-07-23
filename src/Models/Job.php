<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Models;

use Safi\Core\Contracts\ModelInterface;

final class Job implements ModelInterface
{
    public function __construct(private readonly mixed $bean = null) {}

    #[\Override]
    public function unwrap(): mixed
    {
        return $this->bean;
    }

    #[\Override]
    public function getId(): int
    {
        if (is_object($this->bean) && property_exists($this->bean, 'id')) {
            return is_numeric($this->bean->id) ? (int) $this->bean->id : 0;
        }

        return 0;
    }
}
