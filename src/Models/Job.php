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
use stdClass;

final class Job implements ModelInterface
{
    protected mixed $entity;

    public function __construct(mixed $entity = null)
    {
        $this->entity = $entity ?? new stdClass();
    }

    #[\Override]
    public function unwrap(): mixed
    {
        return $this->entity;
    }

    #[\Override]
    public function getId(): int
    {
        $id = $this->getProperty('id', 0);
        return is_numeric($id) ? (int) $id : 0;
    }

    public string $handler {
        get {
            $val = $this->getProperty('handler', '');
            return is_string($val) ? $val : '';
        }
        set {
            $this->setProperty('handler', $value);
        }
    }

    public string $payload {
        get {
            $val = $this->getProperty('payload', '');
            return is_string($val) ? $val : '';
        }
        set {
            $this->setProperty('payload', $value);
        }
    }

    public string $status {
        get {
            $val = $this->getProperty('status', 'pending');
            return is_string($val) ? $val : 'pending';
        }
        set {
            $this->setProperty('status', $value);
        }
    }

    public int $attempts {
        get {
            $val = $this->getProperty('attempts', 0);
            return is_numeric($val) ? (int) $val : 0;
        }
        set {
            $this->setProperty('attempts', $value);
        }
    }

    public string $createdAt {
        get {
            $val = $this->getProperty('created_at', '');
            return is_string($val) ? $val : '';
        }
        set {
            $this->setProperty('created_at', $value);
        }
    }

    private function getProperty(string $property, mixed$default = null): mixed
    {
        if (is_object($this->entity)) {
            return $this->entity->{$property} ?? $default;
        }

        return $default;
    }

    /** @psalm-suppress UnusedMethod */
    private function setProperty(string $property, mixed$value): void
    {
        if (!is_object($this->entity)) {
            $this->entity = new stdClass();
        }

        $this->entity->{$property} = $value;
    }
}
