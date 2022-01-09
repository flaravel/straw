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
$menu->pepperWithStreakyMeat(); // 制作青椒炒肉
