<?php

namespace Straw\Constructs\Container;

use Psr\Container\ContainerInterface;

interface StdContainerInterface extends ContainerInterface
{
    /**
     * 向容器注册绑定
     *
     * @param mixed $abstract 绑定的容器的key
     * @param null $concrete  向容器绑定的对象
     *
     * @return void
     */
    public function bind(mixed $abstract, $concrete = null);
}