<?php

declare(strict_types=1);

namespace Safi\Core\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Safi\Core\Services\SecurityService;

final class SecurityServiceTest extends TestCase
{
    public function testHandlesIpv4AndIpv6CidrRanges(): void
    {
        $config = [
            'trusted_proxies' => ['192.168.1.0/24', '2001:db8::/32'],
        ];

        $security = new SecurityService(new NullLogger(), $config);

        $_SERVER['REMOTE_ADDR'] = '192.168.1.50';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.195';
        $this->assertSame('203.0.113.195', $security->getClientIp());

        $_SERVER['REMOTE_ADDR'] = '2001:db8::1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2001:db8:85a3::8a2e:370:7334';
        $this->assertSame('2001:db8:85a3::8a2e:370:7334', $security->getClientIp());

        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $this->assertSame('10.0.0.1', $security->getClientIp());
    }

    public function testGeneratesAndValidatesCsrfToken(): void
    {
        $security = new SecurityService(new NullLogger());
        $token = $security->getCsrfToken();

        $this->assertTrue($security->validateCsrfToken($token));
        $this->assertFalse($security->validateCsrfToken('invalid-token'));
    }
}
