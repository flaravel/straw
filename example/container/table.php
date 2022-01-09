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
        // 假设工具是另外送的，刚开始老板一直给的是锤子
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
