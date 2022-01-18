### 在容器中实现对参数的注入

结合上文中，如果菜谱类中的构造函数加一个必传参数

代码示例如下:

```php
// ...

// 菜谱类
class Menu
{
    // ... 
    
    // 其他需要的配置
    public array $other = [];
    
    // 注入肉，蔬菜，调味料
    // 加入other 参数
    public function __construct(Meat $meat, Vegetables $vegetables, Flavoring $flavoring, array $other)
    {
        $this->meat = $meat;
        $this->vegetables = $vegetables;
        $this->flavoring = $flavoring;
        $this->other = $other;  
    }
    // ...
}
$container = new Container();
$container->bind('menu', Menu::class);
$menu = $container->get('menu');  // 异常-> 依赖参数异常 Parameter #3 [ <required> array $other ], 缺少必填参数 
```

为啥会报错？

结合上文，我们只处理了，内置类型的参数以及默认参数， 如果传入必传参数那么在实例化成对象的时候因为少了参数而报错，我们可以在调用处传入对应的参数，
并且在容器内部将参数保存起来，将当类前必传参数与传入的参数比较，如果存在就将这个参数注入到类中

代码示例如下：

```php
<?php

// 小明的购物袋
class Container
{
    // ...

    // 增加解析对象传入的参数
    protected array $with = [];

    /**
     * 增加解析容器中的对象的方法
     *
     * @param string $abstract
     * @param array $parameters
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }


    /**
     * 解析传入注入对象名称
     *
     * @param mixed $abstract
     * @param array $parameters 增加传入的参数
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function resolve(string $abstract, array $parameters = []): mixed
    {
        // 获取容器中的对象，如果不存在就返回当前注入key
        $concrete = $this->getConcrete($abstract);

        // 保留参数
        $this->with = $parameters;

        $object = $this->build($concrete);

        // 注入完成清空参数
        $this->with = [];

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
            // 这里是重点
            // 判断当前是否有传入的参数, 存在则将构造函数的参数与传入的参数进行绑定
            $overrideRes = array_key_exists(
                $parameter->name,
                $this->with
            );
            // 如果传入的参数与解析对象需要的构造参数相同则直接跳过循环
            if ($overrideRes) {
                $result[] = $this->with[$parameter->name];
                continue;
            }
            // ... 
        }
        return $result;
    }
}
// ...

// 菜谱类
class Menu
{
    //...


    // 其他需要的配置
    public array $other = [];

    // 注入肉，蔬菜，调味料
    // 加入other 参数
    public function __construct(Meat $meat, Vegetables $vegetables, Flavoring $flavoring, array $other)
    {
        $this->meat = $meat;
        $this->vegetables = $vegetables;
        $this->flavoring = $flavoring;
        $this->other = $other;
    }
    // ...
}

$container = new Container();
$container->bind('menu', Menu::class);
$menu = $container->make('menu', ['other' => [1,2,3]]);  // 正常解析
```

