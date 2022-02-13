<?php

namespace Straw\Core\Foundation\Http;

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
        $body = '<h2>测试</h2>';
        return (new Response(200, ['test' => 'name', 'Content-Type' => 'text/html; charset=utf-8'], $body));
    }
}
