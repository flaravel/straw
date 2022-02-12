<?php

namespace Straw\Core\Http;

use Straw\Core\Http\Factory\Psr17Factory;

class Context
{

    /**
     * @var ServerRequest
     */
    public ServerRequest $request;

    /**
     * @return static
     */
    public static function capture(): static
    {
        $ctx = new static();
        $ctx->request = (new Psr17Factory())->fromGlobals();
        return $ctx;
    }
}