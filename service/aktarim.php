<?php
if ( PHP_SAPI != 'cli' ) {
    die("Works only CLI mode");
}

date_default_timezone_set('Europe/Istanbul');
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

define("PHOTODIR","/var/data/dojo/uploads/photos");
define("SQLITEDB","/var/data/dojo/dojo.db");

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/lib/MongoTools/Tools.php";
require_once __DIR__ . "/lib/SQLite3Tool/SQLite3Ex.php";

use MongoTools\Cast;

try {
    $dotenv = Dotenv\Dotenv::createImmutable("/etc", "dojoservice.env");
    $dotenv->load();
} catch (\Exception $ex) {
    die("Config File can't read");
}
var_dump($_ENV["MONGO_CONNECTION_STRING"]);
$mongo = new \MongoDB\Client($_ENV["MONGO_CONNECTION_STRING"]);
$sqlite = new \SQLite3Tool\SQLite3Ex(SQLITEDB);
$mongo->selectDatabase("dojo")->selectCollection("uye")->deleteMany([]);
$mongo->selectDatabase("dojo")->selectCollection("gelirgider")->deleteMany([]);
$bucket = $mongo->selectDatabase("dojo")->selectGridFSBucket([
    "bucketName" => "dosya"
]);
$bucket->drop();

$arr1 = $sqlite->queryAsArray("SELECT * FROM uye");
for ($i = 0; $i < count($arr1); $i++) {
    $r = $arr1[$i];
    $eski_id = $r["uye_id"];
    $dosya = PHOTODIR . "/" . $r["photo"];
    $uye = [
        "eski_uye_id" => $eski_id,
        "ad" => $r["uye"],
        "ogrenci" => ($r["uye_tur"] == "OGRENCI" ? true : false),
        "dogum" => Cast::toISODate($r["dogum_tarihi"] . "T00:00:00.000Z"),
        "active" => ($r["aktif"] == 1 ? true : false),
        "cinsiyet" => $r["cinsiyet"],
        "email" => $r["eposta"],
        "ekfno" => $r["ekf_no"],
        "img" => null,
        "email_activation" => false,
        "keikolar" => [],
        "sinavlar" => []
    ];

    $yoklamalar = $sqlite->queryAsArray("SELECT * FROM yoklama WHERE uye_id = $eski_id");
    $keikolar = [];
    for ($j = 0; $j < count($yoklamalar); $j++) {
        array_push($keikolar, Cast::toISODate($yoklamalar[$j]["tarih"] . "T00:00:00.000Z"));
    }
    $uye["keikolar"] = $keikolar;

    $seviyeler = $sqlite->queryAsArray("SELECT * FROM seviye WHERE uye_id = $eski_id");
    $sinavlar = [];
    for ($k = 0; $k < count($seviyeler); $k++) {
        array_push($sinavlar, [
            "tarih" => Cast::toISODate($seviyeler[$k]["tarih"] . "T00:00:00.000Z"),
            "aciklama" => $seviyeler[$k]["detaylar"],
            "seviye" => $seviyeler[$k]["tanim"]
        ]);
    }

    if ( !is_null($r["photo"]) && file_exists($dosya) ) {
        $stream = fopen($dosya, 'r');
        if ( $stream !== FALSE ) {
            rewind($stream);
            $fmeta = [
                "time" => new \MongoDB\BSON\UTCDateTime(),
                "file_type" => mime_content_type($dosya),
                "file_name" => $r["photo"],
                "info_text" => "Uye Foto",
                "relation" => "uye.img",
                "index" => 0
            ];
            $bid = $bucket->uploadFromStream($r["photo"], $stream, ["metadata" => $fmeta]);
            $uye["img"] = (string)$bid;
        } else {
            echo "Stream error $dosya , $eski_id".PHP_EOL;
        }
    } else {
        echo "File error $dosya , $eski_id".PHP_EOL;
    }

    $uye["sinavlar"] = $sinavlar;
    $uyeres = $mongo->selectDatabase("dojo")->selectCollection("uye")->insertOne($uye);
    $uye_id = $uyeres->getInsertedId();

    $arrgider = $sqlite->queryAsArray("SELECT * FROM gider g INNER JOIN gider_tur gt ON gt.gider_tur_id = g.gider_tur_id WHERE uye_id = $eski_id");
    for ($m = 0; $m < count($arrgider); $m++) {
        $g = [
            "tur" => "GIDER",
            "tarih" => Cast::toISODate($arrgider[$m]["tarih"] . "T00:00:00.000Z"),
            "uye_id" => $uye_id,
            "tanim" => $arrgider[$m]["gider_tur"],
            "aciklama" => $arrgider[$m]["aciklama"],
            "tutar" => -1 * $arrgider[$m]["tutar"],
            "ay" => 0,
            "yil" => 0,
            "kasa" => null,
            "user_text" => null
        ];
        $mongo->selectDatabase("dojo")->selectCollection("gelirgider")->insertOne($g);
    }

    $arrodeme = $sqlite->queryAsArray("SELECT o.*,ot.odeme_tur FROM odeme o INNER JOIN odeme_tur ot ON ot.odeme_tur_id = o.odeme_tur_id WHERE o.uye_id = $eski_id");
    for($n =0; $n<count($arrodeme); $n++) {
        $o = [
            "tur" => "GELIR",
            "tarih" => Cast::toISODate($arrodeme[$n]["tarih"] . "T00:00:00.000Z"),
            "uye_id" => $uye_id,
            "tanim" => $arrodeme[$n]["odeme_tur"],
            "aciklama" => $arrodeme[$n]["aciklama"],
            "tutar" => $arrodeme[$n]["tutar"],
            "ay" => intval($arrodeme[$n]["ay"]),
            "yil" => intval($arrodeme[$n]["yil"]),
            "kasa" => null,
            "user_text" => null
        ];
        $mongo->selectDatabase("dojo")->selectCollection("gelirgider")->insertOne($o);
    }

}


echo "OK";
