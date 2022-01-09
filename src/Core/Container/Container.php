<?php

namespace Straw\Core\Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Straw\Constructs\Container\StdContainerInterface;

class Container implements StdContainerInterface
{
    /**
     * 单例
     *
     * @var static
     */
    protected static $instance;


    /**
     * @var array
     */
    protected array $instances;

    /**
     * 绑定的注入对象
     *
     * @var array
     */
    protected array $bindings;

    /**
     * 保存传入的参数
     *
     * @var array
     */
    protected array $with = [];

    /**
     * 获取容器实例(单例)
     *
     * @return static
     */
    public static function getInstance(): Container
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * 获取容器实例
     *
     * @param string $id
     *
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    public function get(string $id): mixed
    {
        return $this->resolve($id);
    }

    /**
     * 判断是否存在注入实例
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    /**
     * 从容器中获取指定实例对象
     *
     * @param $abstract
     * @param array $parameters
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function make($abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }


    /**
     * 向容器注册绑定
     *
     * @param mixed $abstract
     * @param null $concrete
     * @param bool $shared
     */
    public function bind(mixed $abstract, $concrete = null, bool $shared = false)
    {
        // 如果为空就将传的注入容器的key当作注入对象
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        // 为了方便操作，将注入的类转为闭包函数
        if (!$abstract instanceof Closure) {
            $concrete = function (Container $container, array $parameters = []) use ($abstract, $concrete) {
                return $container->resolve($concrete, $parameters);
            };
        }
        // 将注入的类绑定到容器中
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * 从容器中解析给定的类型, 如果构造函数中存在参数，那么会根据类型进行递归注入，直到所有参数都注入到该类中
     *
     * @param $abstract
     * @param array $parameters 注入对象实例单参数
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    protected function resolve($abstract, array $parameters = []): mixed
    {
        // 根据注入的 abstract 获取对应的闭包函数，不存在就返回当前的 abstract(这一步意义是处理该类对应的依赖)
        $concrete = $this->getConcrete($abstract);

        if (isset($this->instances[$abstract]) && empty($parameters)) {
            return $this->instances[$abstract];
        }

        // 保存当前实例传入的参数
        $this->with = $parameters;

        $object = $this->build($concrete);

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }
        // 清空当前传入的参数
        array_pop($this->with);

        return $object;
    }

    /**
     * 判断当前注入类是否为单例
     *
     * @param  string  $abstract
     * @return bool
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    public function build($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->with);
        }
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        }
        // 通过反射检测注入的类是否可以被实例化
        if (! $reflector->isInstantiable()) {
            throw new BindingResolutionException("Target [$concrete] is not instantiable.");
        }

        // 通过反射获取当前注入的类是否存在构造函数
        // 如果不存在直接实例化类，存在就将参数注入到该类中
        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            $object = new $concrete();
        } else {
            $instances = $this->resolveDependencies($constructor->getParameters());
            $object = $reflector->newInstanceArgs($instances);
        }

        return $object;
    }

    /**
     * @param array $dependencies
     *
     * @return array
     * @throws BindingResolutionException
     */
    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];
        foreach ($dependencies as $dependency) {
            // 判断构造函数中的参数与传入的参数是否匹配，存在则保存该参数跳过当前循环
            $overrideRes = array_key_exists(
                $dependency->name,
                $this->with
            );
            if ($overrideRes) {
                $results[] = $this->with[$dependency->name];
                continue;
            }

            // 如果传入的参数与构造函数中的参数不匹配
            $results[]  = is_null(Util::getParameterClassName($dependency))
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);     // 若果构造函数参数是类，并且没有在参数中定义，就解析注入类中的依赖
        }

        return $results;
    }

    /**
     * 解析注入类中非必传的参数
     *
     * @param ReflectionParameter $parameter
     *
     * @return mixed|void
     * @throws BindingResolutionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        throw new BindingResolutionException("Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}");
    }

    /**
     * 解析注入类中的依赖
     *
     * @param ReflectionParameter $parameter
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $parameter->isVariadic()
                ? $this->resolveVariadicClass($parameter)
                : $this->make(Util::getParameterClassName($parameter));
        } catch (BindingResolutionException $e) {
            if ($parameter->isDefaultValueAvailable()) {
                array_pop($this->with);

                return $parameter->getDefaultValue();
            }

            if ($parameter->isVariadic()) {
                array_pop($this->with);

                return [];
            }

            throw $e;
        }
    }

    /**
     * 根据绑定的key，获取对应的绑定类
     *
     * @param $abstract
     *
     * @return mixed
     */
    protected function getConcrete($abstract)
    {
        // 获取注入的类,如果没有发现注入对类抛出异常
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }
        return $abstract;
    }
}
