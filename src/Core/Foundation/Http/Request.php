<?php

namespace Straw\Core\Foundation\Http;

use Straw\Core\Http\ServerRequest;
use Straw\Core\Http\Factory\Psr17Factory;
use Straw\Core\Http\Request as StrawRequest;
use Psr\Http\Message\ServerRequestInterface;

class Request extends StrawRequest
{
    /**
     * @var ServerRequest
     */
    public ServerRequest $request;

    /**
     * @return ServerRequestInterface
     */
    public static function capture(): ServerRequestInterface
    {
        return (new Psr17Factory())->fromGlobals();
    }
}
