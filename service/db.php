<?php

use MongoTools\Cast;
use MongoTools\Collection;

require_once __DIR__ . "/lib/MongoTools/Tools.php";
class db
{
    public static function mongo(bool $getLink = false)
    {
        $cs = $_ENV["MONGO_CONNECTION_STRING"];
        $dn = $_ENV["MONGO_DATABASE"];
        $link = (\MongoTools\Cast::toClient($cs));
        if ($getLink) {
            return $link;
        } else {
            return $link->selectDatabase($dn);
        }
    }

    public static function userFind(string $user, string $pass): stdClass
    {
        $mongo = self::mongo();
        $d = [
            "name" => $user,
            "password" => (empty($pass) ? null : md5($pass))
        ];
        $projection = [
            "_id" => 0,
            "role" => 1,
            "text" => 1
        ];
        $result = $mongo->selectCollection("kullanici")->findOne($d, ['projection' => $projection]);
        if (!is_null($result)) {
            $res = new stdClass;
            $res->role = (isset($result["role"]) ? $result["role"] : "USER");
            $res->text = (isset($result["text"]) ? $result["text"] : "");
            return $res;
        } else {
            throw new Exception("Username or password is wrong");
        }
    }

    public static function add(string $collName, $data): string
    {
        $mongo = self::mongo();
        return Collection::add($mongo, $collName, $data);
    }

    public static function uyeler($post)
    {
        $mongo = self::mongo();
        $limit = 1000;
        if (property_exists($post, "limit")) {
            $limit = $post->limit;
        }

        $match = [];
        if (property_exists($post, "active")) {
            $match["active"] = $post->active;
        }

        $lookup = [
            'from' => 'gelirgider',
            'localField' => '_id',
            'foreignField' => 'uye_id',
            'pipeline' => [
                [
                    '$match' => [
                        '$and' => [
                            ['$expr' => ['$eq' => ['$tur', 'GELIR']]],
                            ['$expr' => ['$gt' => ['$ay', 0]]]
                        ]
                    ]
                ]
            ],
            'as' => 'aidatlar'
        ];

        $project = [
            'ad' => 1,
            'cinsiyet' => 1,
            'email' => 1,
            'ekfno' => 1,
            'ogrenci' => 1,
            'active' => 1,
            'dogum' => 1,
            'sinavlar' => 1,
            'keikolar' => 1,
            'aidatlar' => 1
        ];

        $fnc = function ($row) {
            $r = $row;
            if ( !property_exists($r,"keikolar") ) {
                $r->keikolar = [];
            }
            $r->sonkeiko = (count($r->keikolar) > 0 ? max($r->keikolar) : null);

            $arr = [];
            $thismounth = date("Y-m");
            for ($i = 0; $i < count($r->keikolar); $i++) {
                $mounth = substr($r->keikolar[$i], 0, 7);
                if ($mounth != $thismounth) {
                    array_push($arr, $mounth);
                }
            }
            $keikolar = array_unique($arr, SORT_STRING);

            $aidatlar = [];
            for ($i = 0; $i < count($r->aidatlar); $i++) {
                $a = $r->aidatlar[$i];
                $tar = $a->yil . "-" . str_pad($a->ay, 2, "0", STR_PAD_LEFT);
                array_push($aidatlar, $tar);
            }

            $r->aidateksigi = array_diff($keikolar, $aidatlar);
            unset($r->keikolar);
            unset($r->aidatlar);
            return $r;
        };

        return Cast::toTable(
            $mongo->selectCollection("uye")->aggregate([
                ['$match' => $match],
                ['$lookup' => $lookup],
                ['$project' => $project],
                ['$limit' => $limit]
            ]),
            $fnc
        );
    }

    public static function parola($post)
    {
        $mongo = self::mongo();
        $f = [
            "name" => $post->name,
            "password" => (empty($post->password_old) ? null :  md5($post->password_old))
        ];
        $set = [
            '$set' => [
                'password' => md5($post->password_new)
            ]
        ];
        if (property_exists($post, "text")) {
            $set['$set']['text'] = $post->text;
        }
        $num = $mongo->selectCollection("kullanici")->updateOne($f, $set, ["upsert" => false])->getMatchedCount();
        if ($num > 0) {
            return true;
        } else {
            throw new Exception("Username or password is wrong");
        }
    }

    public static function yoklamalar($post)
    {
        $mongo = self::mongo();

        $res = $mongo->selectCollection("uye")->aggregate([
            ['$unwind' => '$keikolar'],
            [
                '$group' => [
                    '_id' => ['keikolar' => '$keikolar'],
                    'sayi' => ['$sum' => 1]
                ]
            ],
            ['$sort' => ['_id' => -1]],
            ['$limit' => 1000],
            [
                '$project' => [
                    '_id' => 0,
                    'tarih' => '$_id.keikolar',
                    'sayi' => 1
                ]
            ]
        ]);

        return Cast::toTable($res);
    }

    public static function yoklama_icindekiler($post)
    {
        $mongo = self::mongo();
        $projection = [
            '_id' => 1,
            'ad' => 1
        ];
        $res = $mongo->selectCollection("uye")->find(['keikolar' =>  Cast::toISODate($post->tarih)], ['projection' => $projection]);
        return Cast::toTable($res);
    }

    public static function yoklama_disindakiler($post)
    {
        $mongo = self::mongo();
        $res = $mongo->selectCollection("uye")->find([
            'active' => true,
            'keikolar' => ['$nin' => [Cast::toISODate($post->tarih)]]
        ], [
            'projection' => [
                '_id' => 1,
                'ad' => 1
            ]
        ]);
        return Cast::toTable($res);
    }

    public static function yoklamaya_ekle($post)
    {
        $mongo = self::mongo();
        $mongo->selectCollection("uye")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$addToSet' => ['keikolar' =>  Cast::toISODate($post->tarih)]]);
        return true;
    }

    public static function yoklamadan_sil($post)
    {
        $mongo = self::mongo();
        $mongo->selectCollection("uye")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$pull' => ['keikolar' =>  Cast::toISODate($post->tarih)]]);
        return true;
    }

    public static function yoklama_uyenin($post)
    {
        $mongo = self::mongo();
        $projection = [
            '_id' => 0,
            'keikolar' => 1
        ];
        $result = $mongo->selectCollection("uye")->findOne(["_id" => Cast::toObjectId($post->_id)], ['projection' => $projection]);
        if (isset($result["keikolar"])) {
            return Cast::transerArray($result["keikolar"]);
        } else {
            return [];
        }
    }

    public static function sinav_ekle($post)
    {
        $mongo = self::mongo();
        $d = [
            "tarih" => Cast::toISODate($post->tarih),
            "seviye" => $post->seviye,
            "aciklama" => $post->aciklama,
            "deger" => $post->deger
        ];
        $mongo->selectCollection("uye")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$pull' => ['sinavlar' =>  ["seviye" => $d["seviye"]]]]);
        $mongo->selectCollection("uye")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$push' => ['sinavlar' =>  $d]]);
        return true;
    }

    public static function sinav_sil($post)
    {
        $mongo = self::mongo();
        $mongo->selectCollection("uye")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$pull' => ['sinavlar' =>  ["seviye" => $post->seviye]]]);
        return true;
    }

    public static function sabitler()
    {
        $mongo = self::mongo();
        return Cast::toTable($mongo->selectCollection("sabit")->find([]));
    }

    public static function sabit($post)
    {
        $mongo = self::mongo();
        $d = [
            "text" => $post->text,
            "value" => $post->value
        ];
        $mongo->selectCollection("sabit")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$pull' => ['data' =>  ["text" => $d["text"]]]]);
        $mongo->selectCollection("sabit")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$push' => ['data' =>  $d]]);
        return true;
    }

    public static function kullanicilar()
    {
        $mongo = self::mongo();
        return Cast::toTable($mongo->selectCollection("kullanici")->find([]));
    }

    public static function kullanici_ekle($post)
    {
        $mongo = self::mongo();
        $post->password = md5($post->password);
        return Collection::add($mongo, 'kullanici', $post);
    }

    public static function kullanici_sil($post)
    {
        $mongo = self::mongo();
        return Collection::remove($mongo, 'kullanici', $post->_id);
    }

    public static function gelir($post)
    {
        $mongo = self::mongo();
        $_id = null;
        $d = [
            "tarih" => Cast::toISODate($post->tarih),
            "tur" => "GELIR",
            "tanim" => $post->tanim,
            "tutar" => $post->tutar,
            "kasa" => $post->kasa,
            "aciklama" => $post->aciklama,
            "yil" => $post->yil,
            "ay" => $post->ay,
            "uye_id" => $post->uye_id
        ];

        if (is_null($post->_id)) {
            $r = $mongo->selectCollection("gelirgider")->insertOne($d);
            $_id = (string)$r->getInsertedId();
        } else {
            $r = $mongo->selectCollection("gelirgider")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$set' => $d], ["upsert" => true]);
            $_id = $post->_id;
        }

        return $_id;
    }

    public static function gelirgider_sil(stdClass $post)
    {
        $mongo = self::mongo();
        $mongo->selectCollection("gelirgider")->deteteOne(["_id" => Cast::toObjectId($post->_id)]);
        return true;
    }

    public static function gelirler_uye(stdClass $post)
    {
        $mongo = self::mongo();
        $sort = [
            "tarih" => -1
        ];
        $limit = 1000;
        $result = $mongo->selectCollection("gelirgider")->find(["uye_id" => Cast::toObjectId($post->_id), "tur" => "GELIR"], [
            'sort' => $sort,
            'limit' => $limit
        ]);
        return Cast::toTable($result);
    }

    public static function gider($post)
    {
        $mongo = self::mongo();
        $_id = null;
        $d = [
            "tarih" => Cast::toISODate($post->tarih),
            "tur" => "GIDER",
            "tanim" => $post->tanim,
            "tutar" => ($post->tutar * -1),
            "kasa" => $post->kasa,
            "aciklama" => $post->aciklama,
            "yil" => $post->yil,
            "ay" => $post->ay,
            "user" => $post->user,
            "uye_id" => null
        ];
        if (is_null($post->_id)) {
            $r = $mongo->selectCollection("gelirgider")->insertOne($d);
            $_id = (string)$r->getInsertedId();
        } else {
            $r = $mongo->selectCollection("gelirgider")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$set' => $d], ["upsert" => true]);
            $_id = $post->_id;
        }

        return $_id;
    }

    public static function giderler($post)
    {
        $mongo = self::mongo();
        $res = $mongo->selectCollection("gelirgider")->find([]);
        return Cast::toTable($res);
    }

    public static function img64($id)
    {
        $mongo = self::mongo();
        $data = $mongo->selectCollection("uye")->findOne(["_id" => Cast::toObjectId($id)]);
        if (is_null($data)) {
            throw new Exception("Imge not found");
        }
        $b64 = $data["img"];

        if (is_string($b64)) {
            return $b64;
        } else {
            throw new Exception("Imge not found");
        }
    }
}
