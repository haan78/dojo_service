<?php
//require "./validate.php";
//Validate::user();
require "./vendor/autoload.php";
require "./db.php";
$_ENV["MONGO_CONNECTION_STRING"]="mongodb://root:12345@mongodb";
$_ENV["MONGO_DATABASE"]="dojo";

/*
$post = new stdClass;
$post->name = "sensei";
$post->password_old = "54321";
$post->password_new = "12345";

var_dump( db::parola($post) );*/

$post = new stdClass;
$post->active = true;

echo "<pre>\n";
var_dump( db::uyeler($post) );

echo "\n</pre>";
