<?php

namespace Tests\Fake;

class FakeRedisConnection
{
    public $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
}