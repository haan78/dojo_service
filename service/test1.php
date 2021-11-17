<?php
//require "./validate.php";
//Validate::user();

use MongoTools\Cast;

require "./vendor/autoload.php";
require "./db.php";
$_ENV["MONGO_CONNECTION_STRING"]="mongodb://root:12345@mongodb";
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