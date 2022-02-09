<?php

namespace Straw\Core\Foundation\Http;

use Straw\Core\Http\Context;
use Straw\Core\Foundation\Application;
use Straw\Constructs\Http\Kernel as KernelContract;

class Kernel implements KernelContract
{
    /**
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new HTTP kernel instance.
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }



    /**
     * @param Context $context
     */
    public function handle($context)
    {
        dd($context);
    }
}