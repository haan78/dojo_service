<?php

require_once "lib/Web/JsonClass.php";
require_once "./validate.php";
use Web\JsonClass;

class service extends JsonClass {
    protected function auth(string $method, callable $abort): void
    {
        Validate::user();
    }

    public function test($post)  {
        return ["post"=>$post];
    }
}

new service(1);