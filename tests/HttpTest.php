<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Tests;

use PHPUnit\Framework\TestCase;
use Safi\Core\Http\Request;
use Safi\Core\Http\Response;

final class HttpTest extends TestCase
{
    public function testRequestSanitizesInputArrays(): void
    {
        $get = ['key' => "  value\0  "];
        $request = new Request($get, [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test?foo=bar']);

        $this->assertSame('value', $request->get('key'));
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/test', $request->getUri());
    }

    public function testDetectsXmlHttpRequest(): void
    {
        $server = ['HTTP_X_REQUESTED_WITH' => 'xmlhttprequest'];
        $request = new Request([], [], $server);

        $this->assertTrue($request->isXhr());
    }

    public function testResponseStoresContentAndHeaders(): void
    {
        $response = new Response('Hello World', 201, ['Content-Type' => 'text/plain']);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Hello World', $response->getContent());
        $this->assertSame(['Content-Type' => 'text/plain'], $response->getHeaders());
    }
}
