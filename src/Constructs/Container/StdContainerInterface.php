<?php

namespace Straw\Constructs\Container;

use Closure;
use TypeError;
use Psr\Container\ContainerInterface;

interface StdContainerInterface extends ContainerInterface
{
    /**
     * 向容器注册绑定
     *
     * @param string $abstract 绑定的容器的key
     * @param Closure|string|null $concrete 向容器绑定的对象
     * @param bool $shared 是否单例绑定
     * @return void
     * @throws TypeError
     */
    public function bind(string $abstract, Closure|string $concrete = null, bool $shared = false);


    /**
     * 向容器注册绑定
     *
     * @param string $abstract 绑定的容器的key
     * @param Closure|string|null $concrete 向容器绑定的对象
     *
     * @return void
     * @throws TypeError
     */
    public function singleton(string $abstract, Closure|string $concrete = null);
}
