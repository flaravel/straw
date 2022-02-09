<?php

use Straw\Core\Http\Context;
use Straw\Core\Foundation\Http\Kernel;
use Straw\Constructs\Http\Kernel as KernelContract;

require __DIR__ . '/vendor/autoload.php';

$app = new Straw\Core\Foundation\Application(dirname(__DIR__));

$app->singleton(
    KernelContract::class,
    Kernel::class
);
$app->make(KernelContract::class)->handle(Context::capture());