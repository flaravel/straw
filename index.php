<?php

use Straw\Core\Foundation\Http\Kernel;
use Straw\Core\Foundation\Application;
use Straw\Constructs\Http\Kernel as KernelContract;
use Straw\Core\Foundation\Http\Request;

require __DIR__ . '/vendor/autoload.php';

$app = new Application(dirname(__DIR__));

$app->singleton(
    KernelContract::class,
    Kernel::class
);
$kernel = $app->make(KernelContract::class);

$response = $kernel->handle(Request::capture())->send();