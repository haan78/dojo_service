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
$post->atcive = true;
$post->email_activation = true;
echo "<pre>\n";
print_r(db::uyeler($post));
echo "\n</pre>";