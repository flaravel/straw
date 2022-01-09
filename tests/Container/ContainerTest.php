<?php

namespace Tests\Container;

use PHPUnit\Framework\TestCase;
use Straw\Core\Container\Container;

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
}