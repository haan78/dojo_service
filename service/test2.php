<?php
//require "./validate.php";
//Validate::user();

use MongoTools\Cast;

require "./vendor/autoload.php";
require "./db.php";
$_ENV["MONGO_CONNECTION_STRING"]="mongodb://root:12345@mongodb";
$_ENV["MONGO_DATABASE"]="dojo";
$post = new stdClass;

//echo "<pre>\n"; var_dump( db::yoklamalar($post) ); echo "\n</pre>";
$post->tarih = '2021-10-31T00:00:00.000Z';
echo "<pre>\n"; var_dump( db::yoklama_disindakiler($post) ); echo "\n</pre>";