<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Services;

use Psr\Log\LoggerInterface;

final class SecurityService
{
    private string $csrfToken = '';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly array $config = [],
    ) {}

    public function getClientIp(): string
    {
        $rawRemote = $_SERVER['REMOTE_ADDR'] ?? null;
        $remoteAddr = is_string($rawRemote) ? $rawRemote : '127.0.0.1';

        /** @var list<string> $trustedProxies */
        $trustedProxies = is_array($this->config['trusted_proxies'] ?? null) ? $this->config['trusted_proxies'] : [];

        foreach ($trustedProxies as $proxy) {
            if ($this->checkIpInCidr($remoteAddr, $proxy)) {
                return $this->resolveProxyIp($remoteAddr);
            }
        }

        return $remoteAddr;
    }

    public function getClientIpHash(): string
    {
        return hash('sha256', $this->getClientIp());
    }

    public function secureSessionStart(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        if (is_string($proto) && strtolower($proto) === 'https') {
            $_SERVER['HTTPS'] = 'on';
            ini_set('session.cookie_secure', '1');
        }

        /** @var array<string, string> $headers */
        $headers = is_array($this->config['headers'] ?? null) ? $this->config['headers'] : [];
        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }

        if (session_status() === PHP_SESSION_NONE) {
            $sessionName = is_string($this->config['sessid'] ?? null) ? $this->config['sessid'] : 'SAFI_SESSID';
            session_name($sessionName);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
            $this->logger->info("Security session initialized: {$sessionName}");
        }

        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->csrfToken = $_SESSION['csrf_token'];
    }

    public function getCsrfToken(): string
    {
        if ($this->csrfToken === '') {
            $this->csrfToken = bin2hex(random_bytes(32));
        }

        return $this->csrfToken;
    }

    public function validateCsrfToken(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $isValid = hash_equals($this->getCsrfToken(), $token);
        if (!$isValid) {
            $this->logger->warning("CSRF token validation failed.");
        }

        return $isValid;
    }

    private function resolveProxyIp(string $default): string
    {
        $cfIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null;
        if (is_string($cfIp) && filter_var($cfIp, FILTER_VALIDATE_IP) !== false) {
            return $cfIp;
        }

        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        if (is_string($forwarded)) {
            $ipList = explode(',', $forwarded);
            $clientIp = trim($ipList[0]);
            if (filter_var($clientIp, FILTER_VALIDATE_IP) !== false) {
                return $clientIp;
            }
        }

        return $default;
    }

    /**
     * Checks if an IP matches a CIDR range (IPv4 & IPv6 Dual-Stack supported).
     */
    private function checkIpInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        $parts = explode('/', $cidr, 2);
        if (count($parts) !== 2) {
            return $ip === $cidr;
        }

        [$subnet, $maskLenStr] = $parts;
        $maskLen = (int) $maskLenStr;

        $ipPacked = @inet_pton($ip);
        $subnetPacked = @inet_pton($subnet);

        if ($ipPacked === false || $subnetPacked === false || strlen($ipPacked) !== strlen($subnetPacked)) {
            return false;
        }

        $bytes = strlen($ipPacked);
        for ($i = 0; $i < $bytes; $i++) {
            if ($maskLen <= 0) {
                break;
            }
            $bits = min($maskLen, 8);
            $mask = (0xFF << (8 - $bits)) & 0xFF;
            if ((ord($ipPacked[$i]) & $mask) !== (ord($subnetPacked[$i]) & $mask)) {
                return false;
            }
            $maskLen -= 8;
        }

        return true;
    }
}
