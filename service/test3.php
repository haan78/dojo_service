<?php 

use MongoTools\Cast;

require "./vendor/autoload.php";
require "./db.php";
$_ENV["MONGO_CONNECTION_STRING"]="mongodb://root:12345@mongodb";
$_ENV["MONGO_DATABASE"]="dojo";

$cs = $_ENV["MONGO_CONNECTION_STRING"];
$dn = $_ENV["MONGO_DATABASE"];
$link = ( \MongoTools\Cast::toClient($cs) );
$db = $link->selectDatabase($dn);
$cursor = $db->command(["eval"=>"uyeListesi()"]);
var_dump($cursor);
