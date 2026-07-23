<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Http;

use RuntimeException;

final class Request
{
    /** @var array<string, mixed> */
    private array $get;

    /** @var array<string, mixed> */
    private array $post;

    /** @var array<string, mixed> */
    private array $server;

    /** @var array<string, mixed> */
    private array $files;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * @param array<mixed, mixed>|null $get
     * @param array<mixed, mixed>|null $post
     * @param array<mixed, mixed>|null $server
     * @param array<mixed, mixed>|null $files
     */
    public function __construct(
        ?array $get = null,
        ?array $post = null,
        ?array $server = null,
        ?array $files = null,
    ) {
        $this->get = $this->sanitizeArray($get ?? $_GET);
        $this->post = $this->sanitizeArray($post ?? $_POST);
        $this->server = $this->sanitizeArray($server ?? $_SERVER);
        $this->files = $this->sanitizeArray($files ?? $_FILES);
    }

    public function getMethod(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';
        $methodString = is_string($method) ? $method : 'GET';
        return strtoupper($methodString);
    }

    public function getUri(): string
    {
        $rawUri = $this->server['REQUEST_URI'] ?? '/';
        $uri = is_string($rawUri) ? $rawUri : '/';
        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }
        return rawurldecode($uri);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function isXhr(): bool
    {
        $header = $this->server['HTTP_X_REQUESTED_WITH'] ?? '';
        return is_string($header) && strtolower($header) === 'xmlhttprequest';
    }

    public function getRawBody(): string
    {
        $body = file_get_contents('php://input');
        if ($body === false) {
            return '';
        }
        return $body;
    }

    /**
     * Copies the raw body stream directly to a target resource.
     *
     * @param resource $target
     */
    public function pipeRawBody(mixed $target): void
    {
        if (!is_resource($target)) {
            throw new RuntimeException('Target stream parameter must be a valid resource.');
        }

        $input = fopen('php://input', 'rb');
        if ($input === false) {
            throw new RuntimeException('Failed to open input stream.');
        }

        stream_copy_to_stream($input, $target);
        fclose($input);
    }

    /**
     * @param array<mixed, mixed> $input
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $input): array
    {
        $output = [];
        foreach ($input as $key => $value) {
            $cleanKey = is_string($key) ? str_replace(chr(0), '', trim($key)) : (string) $key;

            if (is_array($value)) {
                $output[$cleanKey] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $output[$cleanKey] = trim(str_replace(chr(0), '', $value));
            } else {
                $output[$cleanKey] = $value;
            }
        }

        return $output;
    }
}
