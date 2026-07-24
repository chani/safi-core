<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Safi\Core\Contracts\ContainerRegistrarInterface;

final class Assembler implements ContainerInterface, ContainerRegistrarInterface
{
    /** @var array<string, callable(ContainerInterface): object|object> */
    private array $services = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, string> */
    private array $interfaceMap = [];

    public function __construct(private readonly LoggerInterface $logger) {}

    #[\Override]
    public function setInterfaceMap(array $map): void
    {
        $this->interfaceMap = $map;
    }

    #[\Override]
    public function set(string $id, callable | object $factory): void
    {
        $this->services[$id] = $factory;
    }

    #[\Override]
    public function get(string $id): mixed
    {
        if ($this->hasInstance($id)) {
            return $this->instances[$id];
        }

        if (interface_exists($id) && !$this->has($id) && isset($this->interfaceMap[$id])) {
            $concrete = $this->interfaceMap[$id];
            $this->logger->info("Interface mapping resolved: {$id} -> {$concrete}");
            $id = $concrete;
        }

        if (!$this->has($id)) {
            if (class_exists($id)) {
                return $this->autowire($id);
            }
            $this->logger->error("Service not found: {$id}");
            throw new RuntimeException("Service not found: {$id}");
        }

        $this->logger->info("Initializing service: {$id}");
        $factory = $this->services[$id];

        $instance = is_callable($factory) ? $factory($this) : $factory;

        if (!is_object($instance)) {
            throw new RuntimeException("Service '{$id}' must resolve to an object instance.");
        }

        $this->instances[$id] = $instance;

        return $instance;
    }

    #[\Override]
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    private function hasInstance(string $id): bool
    {
        return isset($this->instances[$id]);
    }

    /**
     * @param class-string $class
     */
    private function autowire(string $class): object
    {
        $reflectionClass = new ReflectionClass($class);
        if (!$reflectionClass->isInstantiable()) {
            throw new RuntimeException("Class {$class} is not instantiable.");
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor === null) {
            $instance = new $class();
            $this->instances[$class] = $instance;
            return $instance;
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

	    if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new RuntimeException("Union or Intersection types are not supported for autowiring parameter '{$parameter->getName()}' in class {$class}.");
	    }

            if ($type instanceof ReflectionNamedType) {
                $typeName = $type->getName();

                if ($type->isBuiltin()) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                        continue;
                    }
                    throw new RuntimeException("Cannot autowire built-in type '{$typeName}' for parameter '{$parameter->getName()}' in class {$class}.");
                }

                if (interface_exists($typeName) && !$this->has($typeName) && isset($this->interfaceMap[$typeName])) {
                    $typeName = $this->interfaceMap[$typeName];
                }

                $dependencies[] = $this->get($typeName);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException("Cannot resolve parameter '{$parameter->getName()}' in class {$class}.");
        }

        $instance = $reflectionClass->newInstanceArgs($dependencies);
        $this->instances[$class] = $instance;
        return $instance;
    }
}
