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
        echo "Connected successfully!" . PHP_EOL;
    }


    // 调用serialize() 函数会先调用该魔术方法
    // 应该返回该对象中所有应被序列化的变量名称的数组，如果返回空会抛出了错误
    public function __sleep()
    {
        echo 'serialize __sleep init' . PHP_EOL;
        return ['host', 'username', 'password', 'db'];
    }

    // 调用unserialize() 函数会先调用该魔术方法
    public function __wakeup()
    {
        echo 'unserialize __wakeup init' . PHP_EOL;
        $this->connect();
    }
}

$connect = new MqConnection('127.0.0.1', 'root', 'password', 'test');

$serialize = serialize($connect);
echo 'serialize data -> ' . $serialize . PHP_EOL;

$unserialize = unserialize($serialize);
echo 'unserialize data -> ' . get_class($unserialize) . PHP_EOL;
echo '-------------------分割线----------------------' . PHP_EOL;
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
        echo "Connected successfully!" . PHP_EOL;
    }


    // 不会执行
    public function __sleep()
    {
        echo 'serialize __sleep init' . PHP_EOL;
        return ['host', 'username', 'password', 'db'];
    }

    // 不会执行
    public function __wakeup()
    {
        echo 'unserialize __wakeup init' . PHP_EOL;
        $this->connect();
    }


    public function __serialize()
    {
        echo 'serialize __serialize init' . PHP_EOL;
        return [
            'host' => $this->host,
            'username' => $this->username,
            'password' => $this->password,
            'db' => $this->db
        ];
    }

    public function __unserialize(array $data)
    {
        echo 'unserialize __unserialize init' . PHP_EOL;
        $this->host = $data['host'];
        $this->password = $data['password'];
        $this->username = $data['username'];
        $this->db = $data['db'];
        $this->connect();
    }
}

$connect = new Connection2('127.0.0.1', 'root', 'password', 'test');

$serialize = serialize($connect);
echo 'serialize data -> ' . $serialize . PHP_EOL;

$unserialize = unserialize($serialize);
echo 'unserialize data -> ' . get_class($unserialize) . PHP_EOL;
