<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Contracts;

interface DatabaseDriverInterface
{
    public function connect(string $dsn, string $mode = 'local'): void;

    /**
     * @template T of ModelInterface
     * @param class-string<T> $modelClass
     * @return T
     */
    public function dispenseModel(string $modelClass): ModelInterface;

    /**
     * @template T of ModelInterface
     * @param class-string<T> $modelClass
     * @return T
     */
    public function loadModel(string $modelClass, int $id): ModelInterface;

    /**
     * @template T of ModelInterface
     * @param class-string<T> $modelClass
     * @param array<int|string, mixed> $bindings
     * @return array<int, T>
     */
    public function findModels(string $modelClass, string $criteria = '', array $bindings = []): array;

    /**
     * Counts domain models matching criteria efficiently via the ORM driver.
     *
     * @param class-string<ModelInterface> $modelClass
     * @param array<int|string, mixed> $bindings
     */
    public function countModels(string $modelClass, string $criteria = '', array $bindings = []): int;

    /**
     * @template T of ModelInterface
     * @param class-string<T> $modelClass
     * @param array<int|string, mixed> $bindings
     * @return T|null
     */
    public function findOneModel(string $modelClass, string $criteria = '', array $bindings = []): ?ModelInterface;

    public function storeModel(ModelInterface $model): int;

    /**
     * @template T
     * @param callable(DatabaseDriverInterface): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;

    public function trashModel(ModelInterface $model): void;

    /**
     * @param array<int|string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public function query(string $sql, array $bindings = []): array;

    /**
     * @param array<int|string, mixed> $bindings
     */
    public function exec(string $sql, array $bindings = []): int;
}
