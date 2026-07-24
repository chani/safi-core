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
    /**
     * Establishes the database connection stream.
     */
    public function connect(string $dsn, string $mode = 'local'): void;

    /**
     * Instantiates a pristine, class-bound domain model shell.
     *
     * @template T of ModelInterface
     * @param class-string<T> $modelClass
     * @return T
     */
    public function dispenseModel(string $modelClass): ModelInterface;

    /**
     * Loads a specific domain model by integer primary key.
     *
     * @template T of ModelInterface
     * @param class-string<T> $modelClass
     * @return T
     */
    public function loadModel(string $modelClass, int $id): ModelInterface;

    /**
     * Finds domain models matching criteria.
     *
     * @template T of ModelInterface
     * @param class-string<T> $modelClass
     * @param array<int|string, mixed> $bindings
     * @return array<int, T>
     */
    public function findModels(string $modelClass, string $criteria = '', array $bindings = []): array;

    /**
     * Finds a single domain model matching criteria.
     *
     * @template T of ModelInterface
     * @param class-string<T> $modelClass
     * @param array<int|string, mixed> $bindings
     * @return T|null
     */
    public function findOneModel(string $modelClass, string $criteria = '', array $bindings = []): ?ModelInterface;

    /**
     * Stores and persists a domain model state.
     */
    public function storeModel(ModelInterface $model): int;

    /**
     * Executes an closure block within an isolated database transaction boundary.
     * Guarantees ACID compliance by committing on success or rolling back on Throwable.
     *
     * @template T
     * @param callable(DatabaseDriverInterface): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;

    /**
     * Removes a model record permanently from storage.
     */
    public function trashModel(ModelInterface $model): void;

    /**
     * Executes a raw parameterized query returning array records.
     *
     * @param array<int|string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public function query(string $sql, array $bindings = []): array;

    /**
     * Executes an un-buffered SQL statement (INSERT/UPDATE/DELETE/DDL).
     *
     * @param array<int|string, mixed> $bindings
     */
    public function exec(string $sql, array $bindings = []): int;
}
