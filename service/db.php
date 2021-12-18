<?php

use MongoTools\Cast;
use MongoTools\Collection;

require_once __DIR__ . "/lib/MongoTools/Tools.php";
class db
{

    private static function sendinblue($email, $id, $params)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.sendinblue.com/v3/smtp/email",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            //CURLOPT_POSTFIELDS =>"{ \"templateId\": $id, \"to\": [ { \"email\": \"$email\" } ], \"params\": { \"url\": \"$url\", \"isim\":\"$eczaci\" }}",
            CURLOPT_POSTFIELDS => "{ \"templateId\": $id, \"to\": [ { \"email\": \"$email\" } ], \"params\": " . json_encode($params) . "}",
            CURLOPT_HTTPHEADER => array(
                "api-key: " . $_ENV["SENDINBLUE_APIKEY"],
                "Content-Type: application/json"
            )
        ));

        $response = curl_exec($curl);
        $error = null;
        if ($response === FALSE) {
            $error = curl_error($curl);
        } else {
            $obj = json_decode($response, true);
            if (isset($obj["code"])) {
                $error = $obj["code"] . " / " . $obj["message"];
            } elseif (is_null($obj)) {
                $error = "E-Mail gonderimi basarisiz daha sonra tekrar deneyin($response)";
            }
        }
        curl_close($curl);

        if (!is_null($error)) {
            throw new \Exception($error);
        }
    }

    public static function trDateTime(string $isoDateTime,bool $onlyDate = true) : string {
        $dt = new DateTime($isoDateTime);
        $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
        if ( $onlyDate ) {
            return $dt->format("d.m.Y");
        } else {
            return $dt->format("d.m.Y H:i:s");
        }        
    }

    public static function link(): \MongoDB\Client
    {
        if (isset($_ENV["MONGO_CONNECTION_STRING"])) {
            return new \MongoDB\Client($_ENV["MONGO_CONNECTION_STRING"]);
        } else {
            throw new Exception("ENV MONGO_CONNECTION_STRING not found");
        }
    }

    public static function database(\MongoDB\Client $link): \MongoDB\Database
    {
        if (isset($_ENV["MONGO_CONNECTION_STRING"])) {
            return $link->selectDatabase(trim($_ENV["MONGO_DATABASE"]));
        } else {
            throw new Exception("ENV MONGO_DATABASE not found");
        }
    }

    public static function mongo()
    {
        return self::database(self::link());
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

    public static function sendactivation($post) {
        $mongo = self::mongo();
        $result = $mongo->selectCollection("uye")->findOne(["_id"=>Cast::toObjectId($post->_id)]);
        if ( !is_null($result) && ( isset($result["email"]) ) ) {
            $email = $result["email"];
            $ad = $result["ad"];
            $ed = [
                "email" => $email,
                "uye_id" => Cast::toObjectId($post->_id),
                "create_at" => new \MongoDB\BSON\UTCDateTime(),
                "update_at" => null
            ];
            $res = $mongo->selectCollection("email_activation")->insertOne( $ed );
            $activationid = (string)$res->getInsertedId();
            self::sendinblue($email, 1, [
                "AKTIVASYON_URL" => $_ENV["SERVICE_ROOT"]."/emailactivation/$activationid",
                "UYE_AD" => $ad
            ]);
            return $email;
        } else {
            throw new Exception("Uye epostasi bulunamadi");
        }
    }

    public static function uyeekle($post)
    {
        $set = [
            "ad" => $post->ad,
            "cinsiyet" => $post->cinsiyet,
            "email" => $post->email,
            "ekfno" => $post->ekfno,
            "dogum" => new  \MongoDB\BSON\UTCDateTime(strtotime($post->dogum) * 1000),
            "ogrenci" => $post->ogrenci,
            "active" => $post->active,
            "img" => $post->img,
        ];
        $link = self::link();
        $session = $link->startSession();
        $session->startTransaction();
        $mongo = self::database($link);
        
        $_id = null;
        $activationid = "";
        try {
            if (is_null($post->_id)) {
                $set["email_activation"] = false;
                $set["keikolar"] = [];
                $set["sinavlar"] = [];
                $res = $mongo->selectCollection("uye")->insertOne($set);
                $_id = (string)$res->getInsertedId();
                if ( is_null($_id) || empty($_id) ) {
                    throw new Exception("Nasıl olabilr böyle bişey");
                }
            } else {
                $_id = $post->_id;
                if ( $post->sendemail ) {
                    $set["email_activation"] = false;
                }
                $mongo->selectCollection("uye")->updateOne(["_id" => Cast::toObjectId($_id) ], [ '$set' => $set]);
            }

            if ( $post->sendemail ) {
                $ed = [
                    "email" => $post->email,
                    "uye_id" => Cast::toObjectId($_id),
                    "create_at" => new \MongoDB\BSON\UTCDateTime(),
                    "update_at" => null
                ];
                $result = $mongo->selectCollection("email_activation")->insertOne( $ed );
                $activationid = (string)$result->getInsertedId();
                self::sendinblue($post->email, 1, [
                    "AKTIVASYON_URL" => $_ENV["SERVICE_ROOT"]."/emailactivation/$activationid",
                    "UYE_AD" => $post->ad
                ]);
            }

            $session->commitTransaction();
        } catch (Exception $ex) {
            $session->abortTransaction();
            throw $ex;
        }
        return $_id;
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

        if ( property_exists($post,"_id") && !is_null($post->_id) ) {
            $match["_id"] = Cast::toObjectId($post->_id);
        }

        if ( property_exists($post,"email_activation") && !is_null($post->email_activation) ) {
            $match["email_activation"] = $post->email_activation;
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

        $ucayonce = Cast::toUTCDateTime( (new DateTime())->sub(new \DateInterval('P3M')) );

        $project = [
            'ad' => 1,
            'cinsiyet' => 1,
            'email' => 1,
            'ekfno' => 1,
            'ogrenci' => 1,
            'active' => 1,
            'dogum' => 1,
            'keikolar' => 1,
            'img' => 1,
            'email_activation'=>1,
            'son3ay' => [
                '$size' => [
                    '$filter' => [
                        'input'=>'$keikolar',
                        'as'=>'tarih',
                        'cond' => [ '$gte' => [ '$$tarih', $ucayonce ] ]
                    ]
                ]                
            ],
            'sinavlar' => 1
        ];

        $fnc = function ($row) {
            //var_dump($row); die();
            $r = $row;
            if (!property_exists($r, "keikolar")) {
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

            $r->aidateksigi = array_values(array_diff($keikolar, $aidatlar));
            unset($r->keikolar);
            unset($r->aidatlar);
            return $r;
        };

        return Cast::toTable(
            $mongo->selectCollection("uye")->aggregate([
                ['$match' => $match],
                ['$project' => $project],
                ['$lookup' => $lookup],
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
            'ad' => 1,
            'img' => 1
        ];
        $res = $mongo->selectCollection("uye")->find(['keikolar' =>  Cast::toISODate($post->tarih)], ['projection' => $projection]);
        return Cast::toTable($res);
    }

    public static function yoklama_disindakiler($post)
    {
        $mongo = self::mongo();
        $res = $mongo->selectCollection("uye")->find([
            'active' => true,
            'email_activation' => true, //eposta dogrulamayan keikoya giremez!!!
            'keikolar' => ['$nin' => [Cast::toISODate($post->tarih)]]
        ], [
            'projection' => [
                '_id' => 1,
                'ad' => 1,
                'img' => 1
            ]
        ]);
        return Cast::toTable($res);
    }

    public static function yoklamaya_ekle($post)
    {
        $mongo = self::mongo();
        self::checkMailValidated($mongo,$post->_id);
        $mongo->selectCollection("uye")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$addToSet' => ['keikolar' =>  Cast::toISODate($post->tarih)]]);
        return true;
    }

    public static function yoklamadan_sil($post)
    {
        $mongo = self::mongo();
        self::checkMailValidated($mongo,$post->_id);
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
        self::checkMailValidated($mongo,$post->_id);
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
        self::checkMailValidated($mongo,$post->_id);
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

    private static function checkMailValidated(\MongoDB\Database $mongo,$_id) {
        $projection = ["email_activation"=>1];
        $data = $mongo->selectCollection("uye")->findOne(["_id"=>Cast::toObjectId($_id)],['projection'=>$projection]);
        if ($data!==null) {
            $val = ( isset($data["email_activation"]) ? $data["email_activation"] : false );
            if (!$val) {
                throw new Exception("Uye uyenin epostasi dogrulanmamis");
            }
        } else {
            throw new Exception("Uye bulunamadi");
        }
    }

    public static function gelir($post, $user_text)
    {
        $link = self::link();
        
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
            "uye_id" => Cast::toObjectId($post->uye_id)
        ];

        $d["user_text"] = $user_text;

        $session = $link->startSession();
        $session->startTransaction();
        $mongo = self::database($link);
        try {
            if (is_null($post->_id)) {
                $r = $mongo->selectCollection("gelirgider")->insertOne($d);
                $_id = (string)$r->getInsertedId();
    
            } else {
                $r = $mongo->selectCollection("gelirgider")->updateOne(["_id" => Cast::toObjectId($post->_id)], ['$set' => $d], ["upsert" => true]);
                $_id = $post->_id;
            }
    
            if ( $post->sendemail && $post->ay > 0 ) {
                $data = $mongo->selectCollection("uye")->findOne(["_id" => Cast::toObjectId($post->uye_id)]);
                if (!is_null($data)) {
                    $email = $data["email"];
                    $params = [
                        "UYE_AD" => $data["ad"],
                        "AY" => $post->ay,
                        "YIL" => $post->yil,
                        "TARIH" => self::trDateTime($post->tarih)
                    ];
                    self::sendinblue($email,2,$params);
                } else {
                    throw new Exception("Uye bulunamadi");
                }
            }
            $session->commitTransaction();
        } catch (Exception $ex) {
            $session->abortTransaction();
            throw $ex;
        }
        return $_id;
    }

    public static function gelirgider_sil(stdClass $post)
    {
        $mongo = self::mongo();
        $mongo->selectCollection("gelirgider")->deleteOne(["_id" => Cast::toObjectId($post->_id)]);
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

    public static function gider($post, $user_text)
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
            "uye_id" => Cast::toObjectId($post->uye_id)
        ];
        $d["user_text"] = $user_text;
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
        $res = $mongo->selectCollection("gelirgider")->find(["tur"=>"GIDER"]);
        return Cast::toTable($res);
    }

    public static function gelirgiderlist($post) {
        $mongo = self::mongo();
        $q = [
            "tarih"=>[ '$gte'=>Cast::toISODate($post->baslangic), '$lte' => Cast::toISODate($post->bitis) ]
        ];
        if ( !is_null($post->tur) ) {
            $q["tur"] = $post->tur;
        }

        $res = $mongo->selectCollection("gelirgider")->aggregate([
            [ '$match' => $q ],
            [ '$lookup' => [ 'from' => 'uye', 'localField'=>'uye_id', 'foreignField'=>'_id', 'as'=>'uye' ]  ],
            [ '$unwind' => [ 'path' => '$uye' ] ],
            [ '$project' => [
                '_id'=>1,
                'tur'=>1,
                'kasa'=>1,
                'aciklama'=>1,
                'tarih' => 1,
                'tutar' => 1,
                'tanim' => 1,
                'yil' => 1,
                'ay' => 1,
                'uye_ad' => '$uye.ad',
                'uye_id' => 1,
                'user_text' => 1
            ] ]
        ]);
        return Cast::toTable($res);
    }

    public static function ozet($post) {
        $mongo = self::mongo();
        $baslangic = Cast::toISODate($post->baslangic);
        $bitis = Cast::toISODate($post->bitis);
        $keiko = [
            [ '$project' => [
                '_id'=>1,
                'keikolar'=>[ '$filter' => [
                    'input' => '$keikolar',
                    'as' => 'tarih',
                    'cond' => [ '$and' => [
                        [ '$gte' => ['$$tarih', $baslangic ] ],
                        [ '$lte' => ['$$tarih', $bitis ] ]
                    ]]
                ] ]
            ] ],
            [ '$unwind' => '$keikolar' ],
            [ '$group' => [
                '_id' => '$keikolar',
                't1'=> [ '$sum'=>1 ]
            ] ],
            [ '$group' => [
                '_id' => [ '$dateToString' => ['format'=> '%Y-%m', 'date'=>'$_id' ] ],
                'deger' => [ '$avg'=>'$t1' ]/*,
                'toplam' => [ '$sum' => '$t1' ],
                'sayi' => [ '$sum' => 1 ]*/
            ] ],
            [ '$sort'=>[ '_id' => 1 ] ]
        ];

        $gelir = [
            [ '$match' => [
                'tur' => 'GELIR',
                'tarih' => [ '$gte'=> $baslangic, '$lte'=>$bitis ]
            ] ],
            [ '$group' => [
                '_id' => [ '$dateToString' => ['format'=> '%Y-%m', 'date'=>'$tarih' ] ],
                'deger' => [ '$sum' => '$tutar' ]
            ] ]
        ];

        $gider = [
            [ '$match' => [
                'tur' => 'GIDER',
                'tarih' => [ '$gte'=> $baslangic, '$lte'=>$bitis ]
            ] ],
            [ '$group' => [
                '_id' => [ '$dateToString' => ['format'=> '%Y-%m', 'date'=>'$tarih' ] ],
                'deger' => [ '$sum' => '$tutar' ]
            ] ]
        ];        
        //var_dump($keiko);
        //return Cast::toStdObject($mongo->selectCollection("uye")->aggregate($keiko));
        return (object)[
            "keiko" => Cast::toStdObject($mongo->selectCollection("uye")->aggregate($keiko)),
            "gelir" => Cast::toStdObject($mongo->selectCollection("gelirgider")->aggregate($gelir)),
            "gider" => Cast::toStdObject($mongo->selectCollection("gelirgider")->aggregate($gider))
        ];
    }

    public static function validateEmail(string $_id) {
        $mongo = self::mongo();
        $dt = (new DateTime("now"))->modify('-1 day');
        $mdt = Cast::toUTCDateTime($dt);
        $result = $mongo->selectCollection("email_activation")->findOne([
            "_id"=>Cast::toObjectId($_id),
            'create_at'=>['$gte'=>$mdt]
        ]);
        //var_dump($dt); var_dump($result); die();
        if ( !is_null($result) ) {
            $uye_id = $result["uye_id"];
            $res = $mongo->selectCollection("uye")->updateOne(['_id'=>$uye_id],['$set'=>["email_activation"=>true]]);
            if ( !$res->isAcknowledged() ) {
                throw new Exception("Aktivasyon gerceklestirilemedi");
            }
        } else {
            throw new Exception("Aktivasyon kaydi bulunamadi");
        }
    }
}
