<?php

namespace Tests\Fake;

class FakeDbConnection
{
    public FakeRedisConnection $redis;

    public function __construct(FakeRedisConnection $fakeRedisConnection)
    {
        $this->redis = $fakeRedisConnection;
    }
}