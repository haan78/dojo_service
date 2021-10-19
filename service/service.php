<?php

require_once "lib/Web/JsonClass.php";
require_once "./validate.php";
require_once "./db.php";
use Web\JsonClass;
class service extends JsonClass {
    protected function auth(string $method, callable $abort): void {
        Validate::user();
        $this->role = db::$role;
    }

    public function test($post)  {
        return ["post"=>$post ];
    }
}
service::$SHOW_ERROR_DETAILS = true;
service::$JSON_FLAGS = JSON_PRETTY_PRINT;
new service(1);