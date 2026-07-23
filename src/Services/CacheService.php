<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Services;

use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

final class CacheService implements CacheInterface
{
    private readonly bool $isApcuAvailable;
    private readonly string $storageDir;

    /** @var array<string, mixed> */
    private array $inMemoryCache = [];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        ?string $storageDir = null,
    ) {
        $this->isApcuAvailable = extension_loaded('apcu') && apcu_enabled();
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/safi_cache';

        if (!$this->isApcuAvailable && !is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0750, true);
        }
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->inMemoryCache)) {
            return $this->inMemoryCache[$key];
        }

        if ($this->isApcuAvailable) {
            $success = false;
            $value = apcu_fetch($key, $success);
            if ($success) {
                $this->inMemoryCache[$key] = $value;
                return $value;
            }
            $this->logger?->debug("Cache miss for key: {$key}");
            return $default;
        }

        $filePath = $this->getCacheFilePath($key);
        if (!file_exists($filePath)) {
            $this->logger?->debug("Cache file miss for key: {$key}");
            return $default;
        }

        $rawContent = file_get_contents($filePath);
        if ($rawContent === false) {
            return $default;
        }

        /** @var array{value: mixed, expires_at: int|null}|null $decoded */
        $decoded = json_decode($rawContent, true);
        if (!is_array($decoded) || !array_key_exists('value', $decoded)) {
            @unlink($filePath);
            return $default;
        }

        if ($decoded['expires_at'] !== null && time() > $decoded['expires_at']) {
            @unlink($filePath);
            return $default;
        }

        $this->inMemoryCache[$key] = $decoded['value'];
        return $decoded['value'];
    }

    #[\Override]
    public function set(string $key, mixed $value, null | int | DateInterval $ttl = null): bool
    {
        $this->inMemoryCache[$key] = $value;
        $seconds = $this->ttlToSeconds($ttl);

        if ($this->isApcuAvailable) {
            return apcu_store($key, $value, $seconds);
        }

        $expiresAt = ($seconds > 0) ? time() + $seconds : null;
        $payload = json_encode([
            'value' => $value,
            'expires_at' => $expiresAt,
        ], JSON_THROW_ON_ERROR);

        $filePath = $this->getCacheFilePath($key);
        $result = file_put_contents($filePath, $payload, LOCK_EX) !== false;
        if (!$result) {
            $this->logger?->error("Failed to write cache file for key: {$key}");
        }

        return $result;
    }

    #[\Override]
    public function delete(string $key): bool
    {
        unset($this->inMemoryCache[$key]);

        if ($this->isApcuAvailable) {
            return apcu_delete($key);
        }

        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }

        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        $this->inMemoryCache = [];

        if ($this->isApcuAvailable) {
            return apcu_clear_cache();
        }

        if (is_dir($this->storageDir)) {
            $files = glob($this->storageDir . '/*.json');
            if (is_array($files)) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }

        return true;
    }

    /**
     * @param iterable<mixed, mixed> $keys
     * @return iterable<string, mixed>
     */
    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            if (is_string($key)) {
                yield $key => $this->get($key, $default);
            } elseif (is_int($key)) {
                $strKey = (string) $key;
                yield $strKey => $this->get($strKey, $default);
            }
        }
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    #[\Override]
    public function setMultiple(iterable $values, null | int | DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            $stringKey = is_string($key) ? $key : (is_int($key) ? (string) $key : null);
            if ($stringKey === null) {
                $success = false;
                continue;
            }

            if (!$this->set($stringKey, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @param iterable<mixed, mixed> $keys
     */
    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $stringKey = is_string($key) ? $key : (is_int($key) ? (string) $key : null);
            if ($stringKey === null) {
                $success = false;
                continue;
            }

            if (!$this->delete($stringKey)) {
                $success = false;
            }
        }

        return $success;
    }

    #[\Override]
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    private function getCacheFilePath(string $key): string
    {
        $hash = hash('sha256', $key);
        return $this->storageDir . '/' . $hash . '.json';
    }

    private function ttlToSeconds(null | int | DateInterval $ttl): int
    {
        if (null === $ttl) {
            return 0;
        }
        if ($ttl instanceof DateInterval) {
            $ref = new DateTimeImmutable();
            return $ref->add($ttl)->getTimestamp() - $ref->getTimestamp();
        }
        return $ttl;
    }
}
