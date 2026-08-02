<?php

declare(strict_types=1);

namespace Safi\Core\Services;

use Psr\Log\LoggerInterface;

final class SecurityService
{
    private string $csrfToken = '';

    /**
     * ARCHITECTURE GUARD: $session MUST remain a lazy callable (or null) at instantiation.
     * Eagerly resolving SessionServiceInterface inside SecurityService constructor causes a circular dependency deadlock
     * with SessionService during Container bootstrapping. DO NOT eagerly inject SessionServiceInterface here.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly array $config = [],
        private readonly mixed $session = null,
    ) {}

    public function getClientIp(): string
    {
        $rawRemote = $_SERVER['REMOTE_ADDR'] ?? null;
        $remoteAddr = is_string($rawRemote) ? $rawRemote : '127.0.0.1';

        $trustedProxies = is_array($this->config['trusted_proxies'] ?? null) ? $this->config['trusted_proxies'] : [];

        foreach ($trustedProxies as $proxy) {
            if (is_string($proxy) && $this->checkIpInCidr($remoteAddr, $proxy)) {
                return $this->resolveProxyIp($remoteAddr);
            }
        }

        return $remoteAddr;
    }

    public function getClientIpHash(): string
    {
        return hash('sha256', $this->getClientIp());
    }

    public function getCsrfToken(): string
    {
        if ($this->csrfToken !== '') {
            return $this->csrfToken;
        }

        $sessionObj = is_callable($this->session) ? ($this->session)() : $this->session;

        if (is_object($sessionObj) && method_exists($sessionObj, 'get')) {
            $sessToken = $sessionObj->get('csrf_token');
            if (is_string($sessToken) && $sessToken !== '') {
                $this->csrfToken = $sessToken;
                return $this->csrfToken;
            }
        } elseif (isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) && $_SESSION['csrf_token'] !== '') {
            $this->csrfToken = $_SESSION['csrf_token'];
            return $this->csrfToken;
        }

        $token = bin2hex(random_bytes(32));
        $this->csrfToken = $token;

        if (is_object($sessionObj) && method_exists($sessionObj, 'set')) {
            $sessionObj->set('csrf_token', $token);
        } elseif (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['csrf_token'] = $token;
        }

        return $token;
    }

    public function validateCsrfToken(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $isValid = hash_equals($this->getCsrfToken(), $token);
        if (!$isValid) {
            $this->logger->warning('CSRF token validation failed.');
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
