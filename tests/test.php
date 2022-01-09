<?php
require __DIR__.'/../vendor/autoload.php';

class A {
    public $c;

    public function __construct(C $c)
    {
        $this->c = $c;
    }

    public function __toString()
    {
        return 'a';
    }
}

class C {

}

class Connection {
    public $a;
    public $b;
    public function __construct(A $a, $b)
    {
        $this->a = $a;
        $this->b = $b;
    }
}

app()->bind('conn', Connection::class, true);


dd(app(Connection::class));
