<?php

namespace Tests\Container;

use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeDbConnection;
use Straw\Core\Container\Container;
use Tests\Fake\FakeRedisConnection;

class ContainerTest extends TestCase
{

    protected $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = Container::getInstance();
    }

    public function testContainerClassBindConcrete()
    {
        $class = new \stdClass();
        $this->container->bind('std_class', get_class($class));
        $this->assertInstanceOf(get_class($class), $this->container->get('std_class'));
    }

    public function testContainerClassBindConcreteNull()
    {
        $class = new \stdClass();
        $this->container->bind(get_class($class));
        $this->assertInstanceOf(get_class($class), $this->container->get(get_class($class)));
    }

    public function testContainerClassBindConcreteSingleton()
    {
        $this->container->singleton(FakeDbConnection::class);

        $this->assertInstanceOf(FakeDbConnection::class, $this->container->make(FakeDbConnection::class));

        $this->container->singleton(FakeRedisConnection::class);
        $this->assertInstanceOf(FakeRedisConnection::class, $this->container->make(FakeRedisConnection::class));
    }

    public function testContainerClassBindInstance()
    {
        $this->container->instance(FakeDbConnection::class, new FakeRedisConnection());
        $this->assertInstanceOf(FakeRedisConnection::class, $this->container->make(FakeDbConnection::class));
    }

    public function testContainerClassBindParams()
    {
        $this->container->bind(FakeDbConnection::class);

        $this->assertInstanceOf(FakeDbConnection::class, $this->container->make(FakeDbConnection::class));
    }
}