<?php
date_default_timezone_set('Europe/Istanbul');
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

require_once "./vendor/autoload.php";
require_once "db.php";


$dotenv = Dotenv\Dotenv::createImmutable("/etc","dojoservice.env");
$dotenv->load();

$post = new stdClass();
$post->tur = null;
$post->baslangic = "2021-01-01T00:00:00.000Z";
$post->bitis = "2021-12-31T00:00:00.000Z";
echo "<pre>\n";
print_r(db::gelirgiderlist($post));
echo "\n</pre>";