<?php

namespace Straw\Core\Foundation\Http;

use Straw\Core\Http\Stream;
use Straw\Core\Http\Request;
use Straw\Core\Http\Response;
use Straw\Core\Foundation\Application;
use Straw\Constructs\Http\Kernel as KernelContract;

class Kernel implements KernelContract
{
    /**
     * @var Application|null
     */
    protected ?Application $app = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 处理传入的HTTP请求
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request): Response
    {
        return (new Response(500, ['test' => 'name', 'Content-Type' => 'application/json'], json_encode(['name' => '123'])));
    }
}
