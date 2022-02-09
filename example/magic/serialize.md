### __sleep() , __wakeup() , __serialize() , __unserialize()

1. 在使用 *serialize()* 函数时序列化类时，会检测类中是否会存在一个魔术方法 *__sleep()* ,如果存在，会先会调用该方法。
   与之相反*unserialize()* 函数会检测类型是是否存在 *__wakeup()* 方法，如果存在则会先调用该方法。

注意:*__sleep()* 方法应该返回该类中所有应被序列化的变量名称的数组，并且不能返回类的私有属性，否则会抛出出错误

2. 如果类中有 *__serialize()* 的魔术方法，那么该方法会被执行，且 *__sleep()* 方法会被忽略，并且该方法返回一个键值对的关联数组，如果没返回数组，将会抛出错误，
   相反， *unserialize()* 检查是否存在具有名为 *__unserialize()* 的魔术方法。此函数将会传递从 __serialize() 返回的恢复数组，然后它可以根据需要从该数组中恢复对象的属性。

注意:该特性 PHP 7.4.0 以上可用

### 代码示例

```php
<?php

// step 1
class Connection
{
    protected $link;

    protected $host;

    protected $username;

    protected $password;

    protected $db;

    public function __construct($host, $username, $password, $db)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->db = $db;
    }

    private function connect()
    {
        $this->link = new mysqli($this->host, $this->username, $this->password, $this->db, 3306);

        if ($this->link->connect_error) {
            echo "Connection failed: " . $this->link->connect_error;
            exit();
        }
        echo "Connected successfully!".PHP_EOL;
    }


    // 调用serialize() 函数会先调用该魔术方法
    // 应该返回该对象中所有应被序列化的变量名称的数组，如果返回空会抛出了错误
    public function __sleep()
    {
        echo 'serialize __sleep init'.PHP_EOL;
        return ['host', 'username', 'password', 'db'];
    }

    // 调用unserialize() 函数会先调用该魔术方法
    public function __wakeup()
    {
        echo 'unserialize __wakeup init'.PHP_EOL;
        $this->connect();
    }
}

$connect = new MqConnection('127.0.0.1', 'root', 'password', 'test');

$serialize = serialize($connect);
echo 'serialize data -> '. $serialize.PHP_EOL;

$unserialize = unserialize($serialize);
echo 'unserialize data -> '. get_class($unserialize).PHP_EOL;
echo '-------------------分割线----------------------'.PHP_EOL;
// step 2
class Connection2
{
    protected $link;

    protected $host;

    protected $username;

    protected $password;

    protected $db;

    public function __construct($host, $username, $password, $db)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->db = $db;
    }

    private function connect()
    {
        $this->link = new mysqli($this->host, $this->username, $this->password, $this->db, 3306);

        if ($this->link->connect_error) {
            echo "Connection failed: " . $this->link->connect_error;
            exit();
        }
        echo "Connected successfully!".PHP_EOL;
    }


    // 不会执行
    public function __sleep()
    {
        echo 'serialize __sleep init'.PHP_EOL;
        return ['host', 'username', 'password', 'db'];
    }

    // 不会执行
    public function __wakeup()
    {
        echo 'unserialize __wakeup init'.PHP_EOL;
        $this->connect();
    }


    public function __serialize()
    {
        echo 'serialize __serialize init'.PHP_EOL;
        return [
            'host' => $this->host,
            'username' => $this->username,
            'password' => $this->password,
            'db' => $this->db
        ];
    }

    public function __unserialize(array $data)
    {
        echo 'unserialize __unserialize init'.PHP_EOL;
        $this->host = $data['host'];
        $this->password = $data['password'];
        $this->username = $data['username'];
        $this->db = $data['db'];
        $this->connect();
    }
}

$connect = new MqConnection('127.0.0.1', 'root', 'password', 'test');

$serialize = serialize($connect);
echo 'serialize data -> '. $serialize.PHP_EOL;

$unserialize = unserialize($serialize);
echo 'unserialize data -> '. get_class($unserialize).PHP_EOL;

```

以上程序输出
```text
serialize __sleep init
serialize data -> O:10:"Connection":4:{s:7:"*host";s:12:"127.0.0.1";s:11:"*username";s:4:"root";s:11:"*password";s:17:"password";s:5:"*db";s:2:"test";}
unserialize __wakeup init
Connected successfully!
unserialize data -> Connection
-------------------分割线----------------------
serialize __serialize init
serialize data -> O:11:"Connection2":4:{s:4:"host";s:12:"127.0.0.1";s:8:"username";s:4:"root";s:8:"password";s:17:"password";s:2:"db";s:2:"test";}
unserialize __unserialize init
Connected successfully!
unserialize data -> Connection2
```