<?php
//require "./validate.php";
//Validate::user();
date_default_timezone_set('Europe/Istanbul');
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);


require "./vendor/autoload.php";
require "./db.php";
$_ENV["MONGO_CONNECTION_STRING"]="mongodb://root:12345@mongodb";
//$_ENV["MONGO_CONNECTION_STRING"]="mongodb://root:dQu98KNmtF9@localhost";
$_ENV["MONGO_DATABASE"]="dojo";


$post = new stdClass;
/*$post->name = "sensei";
$post->password_old = "54321";
$post->password_new = "12345";

var_dump( db::parola($post) );*/


$post->active = true;

echo "<pre>\n";
var_dump( db::uyeler($post));

echo "\n</pre>";

/*
echo "<pre>\n";
var_dump(  db::giderler($post) );

echo "\n</pre>";
*/