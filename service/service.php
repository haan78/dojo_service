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

    public function uyeekle($post) {
        return db::add("uye",$post);
    }

    public function uyeler($post) {
        return db::uyeler($post);
    }

    public function parola($post) {
        return db::parola($post);
    }

    public function yoklamalar($post) {
        return db::yoklamalar($post);
    }

    public function yoklama_icindekiler($post) {
        return db::yoklama_icindekiler($post);
    }

    public function yoklama_disindakiler($post) {
        return db::yoklama_disindakiler($post);
    }

    public function yoklamaya_ekle($post) {
        return db::yoklamaya_ekle($post);
    }

    public function yoklamadan_sil($post) {
        return db::yoklamadan_sil($post);
    }

    public function yoklama_uyenin($post) {
        return db::yoklama_uyenin($post);
    }

}
service::$SHOW_ERROR_DETAILS = true;
service::$JSON_FLAGS = JSON_PRETTY_PRINT;
new service(1);