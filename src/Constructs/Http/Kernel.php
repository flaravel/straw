<?php

namespace Straw\Constructs\Http;

use Straw\Core\Http\Request;
use Straw\Core\Http\Response;

interface Kernel
{

    /**
     * 处理传入的HTTP请求
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request): Response;
}
