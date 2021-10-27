<?php

require_once "lib/Web/JsonClass.php";
require_once "./validate.php";
require_once "./db.php";
use Web\JsonClass;
class service extends JsonClass {
    protected function auth(string $method, callable $abort): void {
        //Validate::user();
        $this->role = db::$role;
    }

    public function test($post)  {
        //return ["post"=>$post ];
        //return ["A","B","C"];
        //sleep(6);
        return [
            [ "A"=>1,"B"=>2 ],
            [ "A"=>2,"B"=>1 ],
            [ "A"=>2,"B"=>2 ],
            [ "A"=>1,"B"=>1 ],
        ];
        //return 123;
        //return null;
        //return false;
        //return 23/7;
    }

    public function uyeekle($post) {
        return db::add("uye",$post);
    }

    public function test3() {
        return "Ali Barış Öztürk";
    }

    public function test2() {
        throw new Exception("Test Error");
    }

    public function uyeler($post) {
        return db::uyeler($post);
    }

}
service::$SHOW_ERROR_DETAILS = true;
service::$JSON_FLAGS = JSON_PRETTY_PRINT;
new service(1);