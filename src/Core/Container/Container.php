<?php

namespace Straw\Core\Container;

use Closure;
use Exception;
use ReflectionClass;
use TypeError;
use ReflectionException;
use ReflectionParameter;
use Straw\Constructs\Container\StdContainerInterface;

class Container implements StdContainerInterface
{
    /**
     * @var Container|null
     */
    private static ?Container $instance = null;


    /**
     * 解析后对单例对象
     *
     * @var array
     */
    protected array $instances;

    /**
     * 容器绑定的对象
     *
     * @var array
     */
    protected array $bindings;


    /**
     * 解析后对对象
     *
     * @var bool[]
     */
    protected array $resolved = [];

    /**
     * 传入的参数
     *
     * @var array
     */
    protected array $with = [];


    /**
     * 获取容器的实例
     *
     * @return Container|null
     */
    public static function getInstance(): ?Container
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * 设置当前容器服务
     *
     * @param StdContainerInterface|null $container
     *
     * @return StdContainerInterface|null
     */
    protected static function setInstance(StdContainerInterface $container = null): ?StdContainerInterface
    {
        return static::$instance = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $id
     *
     * @return mixed
     * @throws BindingResolutionException
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function get(string $id): mixed
    {
        try {
            return $this->resolve($id);
        } catch (Exception $e) {
            // 如果有发现注入容器的对象直接抛出异常
            if ($this->has($id)) {
                throw $e;
            }
            throw new EntryNotFoundException($id, $e->getCode(), $e);
        }
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
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    /**
     * 从容器中获取指定实例对象
     *
     * @param $abstract
     * @param array $parameters
     *
     * @return mixed
     * @throws BindingResolutionException|ReflectionException
     */
    public function make($abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }


    /**
     * 绑定单例对象
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     *
     * @return void
     */
    public function singleton(string $abstract, Closure|string $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }


    /**
     * 绑定已经实例化的对象
     *
     * @param string $abstract
     * @param mixed $instance
     *
     * @return mixed
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * 向容器注册绑定对象
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     *
     * @return void
     * @throws TypeError
     */
    public function bind(string $abstract, Closure|string $concrete = null, bool $shared = false)
    {
        $this->dropStaleInstances($abstract);

        // 如果为空就将传的注入容器的key当作注入对象
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // 如果注入的对象不是闭包，则将其转为闭包函数
        if (!$concrete instanceof Closure) {
            if (! is_string($concrete)) {
                $errorMsg = "self::class . '::bind():Argument #2 ($concrete) must be of type Closure|string|null";
                throw new TypeError($errorMsg);
            }
            $concrete = function (Container $container, array $parameters = []) use ($abstract, $concrete) {
                if ($abstract == $concrete) {
                    return $container->build($concrete);
                }

                return $container->resolve($concrete, $parameters);
            };
        }
        // 将注入的类绑定到容器中
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }


    /**
     * 当重新绑定时，删除容器里面的单例对象
     *
     * @param string $abstract
     *
     * @return void
     */
    protected function dropStaleInstances(string $abstract)
    {
        unset($this->instances[$abstract]);
    }

    /**
     * 从容器中解析给定的类型, 如果构造函数中存在参数，那么会根据类型进行递归注入，直到所有参数都注入到该类中
     *
     * @param string $abstract
     * @param array $parameters
     *
     * @return mixed
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        // 获取绑定对容器对象
        $concrete = $this->getConcrete($abstract);

        if (isset($this->instances[$abstract]) && empty($parameters)) {
            return $this->instances[$abstract];
        }

        // 保存获取对象时传入对参数
        $this->with = $parameters;

        // 生成容器对象对具体实例
        $object = $this->build($concrete);

        // 如果是单例注入则注入到单例数组中
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        // 清空当前传入的参数
        $this->with = [];

        $this->resolved[$abstract] = true;

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


    /**
     * 实例化注入类型的具体实例
     *
     * @param Closure|string $concrete
     *
     * @return mixed
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function build(Closure|string $concrete): mixed
    {
        // 如果是闭包直接执行闭包
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->with);
        }

        try {
            // 获取容器对象的反射类
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        }

        // 通过反射检测注入的类是否可以被实例化
        if (! $reflector->isInstantiable()) {
            throw new BindingResolutionException("Target [$concrete] is not instantiable.");
        }

        // 通过反射获取当前注入的类是否存在构造函数
        $constructor = $reflector->getConstructor();

        // 如果存不存在构造函数就直接返回
        if (is_null($constructor)) {
            $object = new $concrete();
        } else {
            // 存在构造函数，检测构造函授是否包含参数
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
     * @throws ReflectionException
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

            // 如果构造函数的参数有类型提示，并且不是内置类型，则递归解析当前的对象类型
            // 如果构造函数没有类型或者是内置类型，则解析是否有默认参数，如果没有默认参数直接抛出异常
            $results[]  = is_null(Util::getParameterClassName($dependency))
                ? $this->resolvePrimitive($dependency)
                : $this->make(Util::getParameterClassName($dependency));
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
     * 根据绑定的key，获取对应的绑定类
     *
     * @param $abstract
     *
     * @return mixed
     */
    protected function getConcrete($abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }
        return $abstract;
    }
}
