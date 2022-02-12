<?php

namespace Straw\Core\Foundation\Http;

use Straw\Core\Http\Context;
use Straw\Constructs\Http\Kernel as KernelContract;

class Kernel implements KernelContract
{

    /**
     * @param Context $context
     */
    public function handle(Context $context)
    {
        dump($context->request);
    }
}