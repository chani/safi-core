<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Http;

final readonly class PsrBridgeAdapter
{
    /**
     * Converts a native Safi Response to array shape for PSR-7 interoperability.
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function toPsr7Shape(Response $response): array
    {
        return [
            'status' => $response->getStatus(),
            'headers' => $response->getHeaders(),
            'body' => $response->getContent(),
        ];
    }
}
