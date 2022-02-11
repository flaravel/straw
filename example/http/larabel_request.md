### Laravel中的Request对象是如何实现的?

在用laravel日常开发中，对`Illuminate\Http\Request` 一定不陌生, Http请求的所有相关参数都会经过这个类，平常做业务开发的时候用的很舒服，但是
很少去想这个类究竟是如何实现的，是怎么做到如此优雅的, 现在就来探究一下
![img.png](img.png)


### 从index.php 开始

这个方法调用 `Request::capture()` 是 `Request` 类的开始, 他生成 

```php
$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()       
)->send();

$kernel->terminate($request, $response);
```
