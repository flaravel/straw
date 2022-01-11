### 从现实生活中理解什么是容器

```text
小明为了解决晚上的晚饭，去超市购物，购买了肉类，蔬菜，调味料等许多做饭需要用到的东西。当结账时小明拿着这么东西肯定无法拎回家，
只能在购买一个购物袋装下这些东西回家，那么这个购物袋就是装下这对象的容器
```
在现实生活中，对容器简最简单的理解就是：**一个可以装下的许多对象的工具，并且可以从容器中取出你想要的对象**

比如上面例子中，购物袋就是一个容器，可以装下各种蔬菜肉类物品等，你想要某个东西时，可以从该容器（购物袋）中取出你要的即可

在代码层面，将购物袋当作一个（**container**）容器类，并且类中有可以装下物品的（**bindings**）属性，通过一个（**bind**）方法往容器中注入需要的对象，然后通过**get**方法取出需要的对象

代码实例子:
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


