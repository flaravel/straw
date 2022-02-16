### 什么是依赖注入？

> php之道： http://phptherightway.p2hp.com/#dependency_injection

在学laravel容器之前，要先了解什么是**依赖注入**，用一个简单的例子来解释，

```text
小明在网上买了一个桌子,收到货物后，附带的零件有，小锤子，木板，钉子，桌脚等，原本这些零件是组装这个桌子的必要条件，结果发现组装桌子的
钉子是螺丝钉，锤子根本不能用，询问老板才知道，是桌子升级了，工具还是给的老版本的，导致这种尴尬局面
```

从代码层面分析，桌子相当于一个类，在没改版之前运行的好好的，突然有一天因为需求变动导致系统升级，原本的工具（锤子）无法适配当前的版本了，
这种只能更改桌子内部结构来适配这个版本了，为了解决这一问题，我们可以将需要的工具从外部注入进去桌子类中

代码实现:
```php

<?php

// 锤子
class Hammer
{
    public function doSomething()
    {
        echo '我是锤子' . PHP_EOL;
    }
}

// 梅花刀
class Knife
{
    public function doSomething()
    {
        echo '我是梅花刀' . PHP_EOL;
        ;
    }
}

// 桌子
class Table
{
    // 工具
    public $tool;

    // 桌脚
    public $leg;

    // 木板
    public $board;

    // 螺丝钉
    public $nail;

    public function __construct()
    {
        // 包装里面一直给的是锤子
        $this->tool = new Hammer();
    }

    public function action()
    {
        // 当实例化类组装桌子当时候，发现需要梅花刀
        // 肯定执行不了
        if (!$this->tool instanceof Knife) {
            echo '不是梅花刀，无法组装' . PHP_EOL;
            return;
        }
        $this->tool->doSomething();
    }
}
(new Table())->action();

echo '-------------------分割线----------------------' . PHP_EOL;

// 桌子
class Table2
{
    // 工具
    public $tool;

    // 桌脚
    public $leg;

    // 木板
    public $board;

    // 螺丝钉
    public $nail;

    // 不管工具怎么变，都可以不用改变table里面的代码了
    public function __construct($tool)
    {
        $this->tool = $tool;
    }

    public function action()
    {
        $this->tool->doSomething();
    }
}
$knife = new Knife();
$hammer = new Hammer();
(new Table2($knife))->action();
(new Table2($hammer))->action();
```
以上代码输出:
```text
不是梅花刀，无法组装
-------------------分割线----------------------
我是梅花刀
我是锤子
```
