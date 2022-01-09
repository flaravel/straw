## Start Learning PHP - 依赖注入

在学laravel容器之前，要先了解什么是**依赖注入**，用一个简单的例子来解释，

> 更详细的解释 http://fabien.potencier.org/what-is-dependency-injection.html

```text
小明在网上买了一个桌子，需要组装，收到货物后，附带的零件有，小锤子，木板，钉子，桌脚等，原本这些零件是组装这个桌子的必要条件，结果发现组装桌子的钉子是螺丝钉，锤子根本不能用，小明心想什么老板，组装桌子要梅花螺丝刀你却给个锤子，没啥用，只能花钱在去买一个梅花刀才行。
```
假设桌子送的工具一直是锤子，后面因为桌子内部升级，不要锤子了，需要梅花刀，因为工具是在桌子包装里面，拆开包装，将锤子换成梅花刀，这就破坏包装了呀。 怎么样才能避免出这样的问题呢， 那么一开始锤子不附带在包装里面，老板可以看型号，单独送工具，就再也不用担心工具给错的问题了

代码示例:
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

只要从构造函数注入工具，内部结构怎么升级，组装的步骤在不改变类的情况下，就可以适用所有的工具了

