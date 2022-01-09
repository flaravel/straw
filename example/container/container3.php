<?php

// 小明的购物袋
class Container
{
    // 晚上做饭需要用到的材料
    protected array $bindings = [];

    /**
     * 获取购买时的对象
     *
     * @param string $id
     *
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     */
    public function get(string $id): mixed
    {
        return $this->resolve($id);
    }

    /**
     * 解析传入注入对象名称
     *
     * @param mixed $abstract
     *
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     */
    public function resolve(mixed $abstract): mixed
    {
        // 获取容器中的对象，如果不存在就返回当前注入key
        $concrete = $this->getConcrete($abstract);

        return $this->build($concrete);
    }


    /**
     * 生成对象
     *
     * @param $concrete
     *
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     */
    public function build($concrete): mixed
    {
        // 这里不用 new 关键字来实例化对象，反射可以处理类的一系列操作，可以自行查看反射的官方文档
        // 可以通过反射知道注入对象是否有构造函数，并且可以拿到构造函数中的参数
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new Exception("当前类 [$concrete] 没有被找到.");
        }

        // 获取当前对象的构造函数，
        $constructor = $reflector->getConstructor();

        // 如果为空，说明该对象构造函数无注参数，则直接new 当前对象并返回
        // 如果存在参数，则获取构造函数参数，并且解析参数是否为对象还是普通参数
        if (is_null($constructor)) {
            $object = new $concrete();
        } else {
            // 返回一个数组
            $parameters = $constructor->getParameters();
            // 解析构造函数的参数
            $instances = $this->resolveDependencies($parameters);
            $object = $reflector->newInstanceArgs($instances);
        }

        // 返回最终的实例化对象
        return $object;
    }

    /**
     * 获取注入后的参数
     *
     * @param array $parameters
     *
     * @return array
     * @throws ReflectionException
     * @throws Exception
     */
    public function resolveDependencies(array $parameters): array
    {
        $result = [];

        foreach ($parameters as $parameter) {
            // 获取当前参数类型
            $type = $parameter->getType();

            // 1.检测是否设置类参数类型，如果设置就返回对应的类型对象，否则返回NULL, 并且检测是否为内置函数，
            if (!is_null($type) && !$type->isBuiltin()) {
                // 不为空，且不是内置函数，就肯定是一个对象，获取对象名称递归获取当前注入的对象实例
                $result[] = $this->build($type->getName());
            // 2.判断是否设置了默认参数，设置了就直接使用当前的默认参数
            } elseif ($parameter->isDefaultValueAvailable()) {
                $result[] = $parameter->getDefaultValue();
            // 3. 设置了参数，但是没有默认值就返回异常（这种情况需要获取容器对象的时候传入对应的参数）
            } else {
                throw new Exception("依赖参数异常 $parameter, 缺少必填参数");
            }
        }
        return $result;
    }

    /**
     * 判断取的对象是否存在购物袋中
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    /**
     * 向购物袋中放对象
     *
     * @param mixed $abstract 放入的名称key
     * @param string|null $concrete 具体的对象
     */
    public function bind(mixed $abstract, string $concrete = null)
    {
        // 往袋子里面放
        $this->bindings[$abstract] = $concrete;
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
        // 获取注入的类,如果没有发现注入的对象就返回当前获取对象的key
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract];
        }
        return $abstract;
    }
}

// 蔬菜
class Vegetables
{
    public function name(): string
    {
        return '我是蔬菜';
    }

    // 获取青椒
    public function getPepper(): string
    {
        return '青椒';
    }

    // 获取西红柿
    public function getTomato(): string
    {
        return '西红柿';
    }
}

//肉
class Meat
{
    public function name(): string
    {
        return '我是肉类';
    }

    // 获取鸡蛋
    public function getEgg(): string
    {
        return '鸡蛋';
    }

    // 获取五花肉
    public function getStreakyMeat(): string
    {
        return '五花肉';
    }
}

// 调味料
class Flavoring
{
    public function name(): string
    {
        return '我是调味料';
    }

    // 盐
    public function getSalt(): string
    {
        return '盐';
    }

    // 生抽
    public function getScSauce(): string
    {
        return '生抽';
    }
}

// 既然要做晚饭，肯定要看菜谱了，看看菜谱做的顺序一次照做就好
class Menu
{
    // 肉类
    public Meat $meat;

    // 蔬菜类
    public Vegetables $vegetables;

    // 调味料
    public Flavoring $flavoring;

    // 注入肉，蔬菜，调味料
    public function __construct(Meat $meat, Vegetables $vegetables, Flavoring $flavoring)
    {
        $this->meat = $meat;
        $this->vegetables = $vegetables;
        $this->flavoring = $flavoring;
    }

    // 青椒炒肉
    public function pepperWithStreakyMeat()
    {
        // 1.拿出切好的五花肉和西红柿
        $meat = $this->meat->getStreakyMeat();
        $pepper = $this->vegetables->getPepper();
        // 2.拿出调味料
        $sc = $this->flavoring->getScSauce();
        $salt = $this->flavoring->getSalt();
        echo '先拿出切好的->' . $meat . PHP_EOL;
        echo '然后拿出切好的->' . $pepper . PHP_EOL;
        echo '最后加上->' . $sc . '与' . $salt . PHP_EOL;
        echo '开始暴炒青椒炒肉...' . PHP_EOL;
        echo '上菜。' . PHP_EOL;
    }

    // 西红柿炒鸡蛋
    public function tomatoWithEgg()
    {
        //
    }
}

$container = new Container();
$container->bind('menu', Menu::class);
$menu = $container->get('menu');
$menu->pepperWithStreakyMeat(); // 制作青椒炒肉
