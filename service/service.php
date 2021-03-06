<?php

require_once "lib/Web/JsonClass.php";
require_once "./validate.php";
require_once "./db.php";
use Web\JsonClass;
class service extends JsonClass {
    protected string $text;
    protected function auth(string $method, callable $abort): void {        
        $res = Validate::user();
        $this->role = $res->role;
        $this->text = $res->text;
    }

    public function uyeekle($post) {
        return db::uyeekle($post);
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

    public function sinav_ekle($post) {
        return db::sinav_ekle($post);
    }

    public function sinav_sil($post) {
        return db::sinav_sil($post);
    }

    public function sabitler($post) {
        return db::sabitler();
    }

    public function sabit($post) {
        return db::sabit($post);
    }

    public function kullanici() {
        return [
            "role" => $this->role,
            "text" => $this->text
        ];
    }

    public function kullanicilar() {
        return db::kullanicilar();
    }

    public function kullanici_ekle($post) {
        if ( $this->role == "ADMIN" ) {
            return db::kullanici_ekle($post);
        } else {
            throw new \Exception("Sadece yönetici rolündeki kişiler bu işlemi yapabilir");
        }        
    }

    public function kullanici_sil($post) {
        if ( $this->role == "ADMIN" ) {
            return db::kullanici_sil($post);
        } else {
            throw new \Exception("Sadece yönetici rolündeki kişiler bu işlemi yapabilir");
        }        
    }

    public function giderler($post) {
        return db::giderler($post);
    }

    public function gider($post) {
        return db::gider($post,$this->text);
    }

    public function gerlir_uye($post) {
        return db::gelirler_uye($post);
    }

    public function gelirgider_sil($post) {
        return db::gelirgider_sil($post);
    }

    public function gelir($post) {
        return db::gelir($post,$this->text);
    }

    public function gelirgiderlist($post) {
        return db::gelirgiderlist($post);
    }

    public function sendactivation($post) {
        return db::sendactivation($post);
    }

    public function ozet($post) {
        return db::ozet($post);
    }

}
service::$SHOW_ERROR_DETAILS = true;
service::$JSON_FLAGS = JSON_PRETTY_PRINT;
