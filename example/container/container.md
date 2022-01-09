## Start Learning PHP - 容器

### 1.什么是容器？

> 维基百科解释：https://zh.wikipedia.org/wiki/容器

```text
小明为了解决晚上的晚饭，去超市购物，购买了肉类，蔬菜，调味料等许多做饭需要用到的东西。当结账时小明拿着这么东西肯定无法拎回家，
只能在购买一个购物袋装下这些东西回家，那么这个购物袋就是装下这对象的容器
```
在现实生活中，对容器简最简单的理解就是：**一个可以装下的许多对象的工具，并且可以从容器中取出你想要的对象**

比如去超市买菜，购物袋就是一个容器，可以装下各种蔬菜肉类等，回家之后，你想要某个东西时，可以从该容器中取出你要的即可

用代码实现上面例子:
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
     * @throws Exception
     */
    public function get(string $id)
    {
        // 1.取的对象是否存在购物袋里面
        if ($this->has($id)) {
            // 2.如果存在则返回要取的对象
            $concrete = $this->bindings[$id];
            if (class_exists($concrete)) {
                return new $concrete();
            }
        }
        // 取不到就给个错误
        throw new Exception("当前 [$id] 服务没找到");
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
     * 向购物袋中放东西
     *
     * @param mixed $abstract 放入的名称
     * @param string|null $concrete 具体的东西
     */
    public function bind(mixed $abstract, string $concrete = null)
    {
        // 往袋子里面放
        $this->bindings[$abstract] = $concrete;
    }
}

// 蔬菜
class Vegetables
{
    public function name(): string
    {
        return '我是蔬菜';
    }
}

//肉
class Meat
{
    public function name(): string
    {
        return '我是肉类';
    }
}

// 调味料
class Flavoring
{
    public function name(): string
    {
        return '我是调味料';
    }
}

$container = new Container();
$container->bind('vegetables', Vegetables::class);
$container->bind('meat', Meat::class);
$container->bind('flavoring', Flavoring::class);
$vegetables = $container->get('vegetables');
$meat = $container->get('meat');
$flavoring = $container->get('flavoring');
var_dump($vegetables->name());  // 输出我是蔬菜
var_dump($meat->name());        // 输出我是肉类
var_dump($flavoring->name());    // 输出我是调味料
```

上面代码实现了一个可以放对象，也可以取出对象的类，这个类就相当于一个容器，当然这是一个最简单的实现

### 2. 如何用容器实现依赖注入 ？

```text
小明购物回家后，做一顿晚饭肯定不只一道菜，想做最喜欢吃的【青椒炒肉】，【西红柿炒鸡蛋】，【西葫芦汤】
```
按照上面想要吃的菜,我们可以加一个菜谱类，再将肉，蔬菜，调味料等类注入到菜谱中，这样只需要往容器中加入菜谱类就可以了，这样直接操作菜谱制作菜谱上对应的菜即可

代码实现如下：
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
     * @throws Exception
     */
    public function get(string $id)
    {
        // 1.取的对象是否存在购物袋里面
        if ($this->has($id)) {
            // 2.如果存在则返回要取的对象
            $concrete = $this->bindings[$id];
            if (class_exists($concrete)) {
                return new $concrete();
            }
        }
        // 取不到就给个错误
        throw new Exception("当前 [$id] 服务没找到");
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
     * 向购物袋中放东西
     *
     * @param mixed $abstract 放入的名称
     * @param string|null $concrete 具体的东西
     */
    public function bind(mixed $abstract, string $concrete = null)
    {
        // 往袋子里面放
        $this->bindings[$abstract] = $concrete;
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
        return '切好的青椒';
    }

    // 获取西红柿
    public function getTomato(): string
    {
        return '切好的西红柿';
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
$container->bind('menu', Menu::class);
$menu = $container->get('menu');  // 会报错 Too few arguments to function Menu::__construct(), 0 passed 
$menu->pepperWithStreakyMeat(); // 制作青椒炒肉方法
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

> 利用反射 ReflectionClass 实现依赖注入，官方文档 https://www.php.net/manual/zh/book.reflection.php

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

$container = new Container();
$container->bind('menu', Menu::class);
$menu = $container->get('menu');  // 正常注入依赖，肉，蔬菜，调味料
$menu->pepperWithStreakyMeat(); // 制作青椒炒肉方法
```
最终上里代码输出：
```text
先拿出切好的->五花肉
然后拿出切好的->青椒
最后加上->生抽与盐
开始暴炒青椒炒肉...
上菜。
```

