<?php

namespace Tests\Container;

use Straw\Core\Http\Uri;
use Straw\Core\Http\Stream;
use Straw\Core\Http\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class RequestTest extends TestCase
{

    public function testRequestUriMayBeString()
    {
        $r = new Request('GET', '/');
        $this->assertEquals('/', (string) $r->getUri());
    }

    public function testRequestUriMayBeUri()
    {
        $uri = new Uri('/');
        $r = new Request('GET', $uri);
        $this->assertSame($uri, $r->getUri());
    }

    public function testValidateRequestUri()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('无法解析 URI: "///"');
        new Request('GET', '///');
    }

    public function testCanConstructWithBody()
    {
        $r = new Request('GET', '/', [], 'test');
        $this->assertInstanceOf(StreamInterface::class, $r->getBody());
        $this->assertEquals('test', (string)$r->getBody());
    }
}
