<?php

namespace Tests\Fake;

class FakeRedisConnection
{
    public array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
}