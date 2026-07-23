<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core;

use Psr\Log\AbstractLogger;
use Stringable;

final class Logger extends AbstractLogger
{
    private readonly float $startTime;

    /** @var array<int, string> */
    private array $logs = [];

    public function __construct(private readonly bool $enabled = true)
    {
        $this->startTime = microtime(true);
    }

    /**
     * @param array<array-key, mixed> $context
     */
    #[\Override]
    public function log(mixed $level, string | Stringable $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $currentTime = microtime(true);
        $deltaTime = number_format($currentTime - $this->startTime, 6);
        $levelString = is_string($level) || $level instanceof Stringable ? (string) $level : 'info';
        $formattedLevel = strtoupper($levelString);
        $interpolatedMessage = $this->interpolate((string) $message, $context);

        $entry = sprintf('[%10s] [%s] %s', $deltaTime, $formattedLevel, $interpolatedMessage);
        $this->logs[] = $entry;

        error_log("APP_LOG: {$entry}");
    }

    /**
     * @return array<int, string>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    public function getDmesg(): string
    {
        return implode(PHP_EOL, $this->logs);
    }

    /**
     * @param array<array-key, mixed> $context
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_scalar($val) || $val instanceof Stringable) {
                $replace['{' . $key . '}'] = (string) $val;
            }
        }

        return strtr($message, $replace);
    }
}
