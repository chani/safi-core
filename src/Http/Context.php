<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Http;

use Psr\Log\LoggerInterface;

final class Context
{
    public function __construct(
        public readonly Request $request,
        public Response $response,
        public readonly LoggerInterface $logger,
    ) {}
}
