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
    public function bind(string $abstract, string $concrete = null)
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
var_dump($meat->name());    // 输出我是肉类
var_dump($flavoring->name());    // 输出我是调味料
