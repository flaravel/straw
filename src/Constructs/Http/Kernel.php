<?php

namespace Straw\Constructs\Http;

use Straw\Core\Http\Context;

interface Kernel
{

    public function handle(Context $context);
}