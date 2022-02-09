<?php

namespace Straw\Core\Http;

use Straw\Core\Http\Factory\ServerRequestFactory;

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
        $ctx->request = (new ServerRequestFactory())->createServerRequestFormBase();
        return $ctx;
    }
}