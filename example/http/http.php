<?php

//$server = $_SERVER;
//
//// get
//$get = function ($key = '') {
//    if ($key) {
//        return $_GET[$key] ?? '';
//    }
//    return $_GET;
//};
//
//// post
//$post = function ($key = '') {
//    if ($key) {
//        return $_POST[$key] ?? '';
//    }
//    return $_POST;
//};
//
//// 协议版本号
//$protocol = $server['SERVER_PROTOCOL'];
//
//// 请求方法
//$method = $server['REQUEST_METHOD'];
//
//// 获取HTTP协议版本与请求方法
//echo $method.' '.$protocol.'<br/>';
//
//// 请求HOST
//echo $server['SERVER_NAME'].'<br/>';
//
//// 请求端口
//echo $server['SERVER_PORT'].'<br/>';
//
//echo '<pre>';
//
//// 获取GET数据信息
//print_r($get());
//
//// 获取POST信息
//print_r($post());
//
//// 获取cookie
//print_r($_COOKIE);
//
//// 获取文件
//print_r($_FILES);
//
//// 获取请求头信息
//print_r(getallheaders());

echo '<hr/>';


// 设置响应信息
$headerStr = sprintf('HTTP/%s %s %s', '1.1', 200, 'Test Http Ok');
header($headerStr);
header('test: test header1');
header('test2: test header2');
header('Content-Type: application/json');

$data = ['foo' => 'bar'];

echo json_encode($data);
