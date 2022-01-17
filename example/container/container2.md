### 在容器中实现对象的依赖

在实际开发中，我想大家经常会看到如下代码:

```php

class UserXXX {}

class UserService
{
    protected $user;
    
    // 如果直接实例化该类，肯定好要传对应的参数，不传然肯定报错
    public function __construct(UserXXX $user) {
        $this->user = $user;
    }
    
    // do something
}
new UserServuce(new UserXXX()); // 正常实例化
app(UserService::class); // 不报错，why？ 这里面就实现类依赖注入
```

接下来拿上文举例 ，我们将小明购买的菜列一个菜谱，有青椒炒肉，西红柿炒鸡蛋等，而做这些菜的前提必须有所说的几样材料，将肉类，蔬菜类，调味料等类注入到菜谱中
，最后只需要在菜谱加制作的放即可

代码实现如下：
```php
<?php

// ... 

// 结合上文的代码增加菜谱类
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
        echo '开始烹饪青椒炒肉...' . PHP_EOL;
        echo '上菜。' . PHP_EOL;
    }

    // 西红柿炒鸡蛋
    public function tomatoWithEgg()
    {
        //
    }
}

$container = new Container();
$container->bind('menu', Menu::class);  // 注入
$menu = $container->get('menu');  // 获取会报错 Too few arguments to function Menu::__construct(), 0 passed
```

如果按照上面这样实现肯定会抛出异常，因为在容器类中直接 *new* 注入的对象，但是我们实现的菜谱构造函数中有注入的各种对象，这样肯定会报语法错误

```php
 if ($this->has($id)) {
    // 2.如果存在则返回要取的对象
    $concrete = $this->bindings[$id];
    if (class_exists($concrete)) {
        return new $concrete(); // 这里会抛出异常，我需要的，肉，蔬菜，调味料没有传入
    }
}
```

接下来要改造这个容器类，将菜谱类需要的蔬菜，肉，调味料一起注入到该类中
改造后的代码实例：

```php
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
        // 这里不用 new 关键字来实例化对象，反射可以处理类的一系列高级操作，可以自行查看反射的官方文档
        // 可以通过反射知道注入的对象是否有构造函数，并且可以拿到构造函数中的参数
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new Exception("当前注入类 [$concrete] 没有被找到.");
        }

        // 获取当前对象的构造函数
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
            // 将解析后的参数传入到菜单类中，重新实例化
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
                $result[] = $this->build($type->getName()); // 只要有依赖的类，就会递归调用解析
            // 2.判断是否设置了默认参数，设置了就直接使用当前的默认参数
            } elseif ($parameter->isDefaultValueAvailable()) {
                $result[] = $parameter->getDefaultValue();
            // 3. 设置了参数，但是没有默认值就返回异常（这种情况需要获取容器对象的时候传入对应的参数,下文在继续更新）
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
     * @param string $abstract 放入的名称key
     * @param string|null $concrete 具体的对象
     */
    public function bind(string $abstract, string $concrete = null)
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

$container = new Container();
$container->bind('menu', Menu::class);
$menu = $container->get('menu');  // 正常注入依赖，肉，蔬菜，调味料
$menu->pepperWithStreakyMeat(); // 制作青椒炒肉方法
```
最终以上代码输出：
```text
先拿出切好的->五花肉
然后拿出切好的->青椒
最后加上->生抽与盐
开始暴炒青椒炒肉...
上菜。
```
