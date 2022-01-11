<?php

function hr($fu)
{
    echo '-------------------' . $fu . '->end-------------------' . PHP_EOL;
    echo PHP_EOL;
}

// 99乘法表
function multiplicationTable()
{
    for ($i = 1; $i <= 9; $i++) {
        for ($j = 1; $j <= $i; $j++) {
            echo $i == $j ? "{$i}x{$j}=" . ($i * $j) . PHP_EOL : "{$i}x{$j}=" . ($i * $j) . " ";
        }
    }
}
multiplicationTable();
hr('multiplicationTable');

// 反转字符串
function reversalStr($str): string
{
    // 1.先查看当前字符串的长度
    $strCount = mb_strlen($str);

    // 2.循环字符串的长度,在通过substr截取存入数组
    $strArr = [];
    for ($i = 1; $i <= $strCount; $i++) {
        $strArr[] = mb_substr($str, -$i, 1);
    }

    return implode('', $strArr);
}
$str = 'laravel';
echo "反转字符串[$str]->" . reversalStr($str) . PHP_EOL;
hr('reversalStr');

// 反转一唯数组中的元素
function reversalArr(&$arr)
{
    // 主要是将左右两边的元素相互调换
    // 1.记录左边下标为0 开始值
    $left = 0;
    // 2.记录右边的下标数组长度减1，因为下表从0开始的
    $right = count($arr) - 1;

    // 3. 如果左边小于右边，肯定没有对换完成，直到left不小于right为止
    while ($left < $right) {
        // 4. 记录当前左边的值
        $temp = $arr[$left];
        // 5. 将右边的值赋予左边，在自增
        $arr[$left++] = $arr[$right];
        // 6. 将临时定义左边的值在赋予右边，在自减
        $arr[$right--] = $temp;
    }
}
$arr = ['a','b','c','d','e','f'];
reversalArr($arr);
print_r($arr);
hr('reversalArr');
