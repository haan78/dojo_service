<?php

use MongoTools\Cast;
use MongoTools\Collection;

require_once __DIR__ . "/lib/MongoTools/Tools.php";
class db {
    public static $role = "";
    private static $mongo = null;
    public static function mongo() {
        if (is_null(self::$mongo)) {
            $cs = $_ENV["MONGO_CONNECTION_STRING"];
            $dn = $_ENV["MONGO_DATABASE"];
            $link = ( \MongoTools\Cast::toClient($cs) );            
            self::$mongo = $link->selectDatabase($dn);
        }

        return self::$mongo;
    }

    public static function userFind(string $user,string $pass) : bool {
        $mongo = self::mongo();
        $d = ["name" => $user, "password" => md5($pass)];        
        $result = $mongo->selectCollection("user")->findOne($d);
        if ( !is_null($result) ) {
            self::$role = ( isset($result["role"]) ? $result["role"] : "USER" );
            return true;
        } else {
            return false;
        }        
    }

    public static function add(string $collName,$data) : string {
        $mongo = self::mongo();
        return Collection::add($mongo,$collName,$data);
    }

    public static function uyeler($post) {
        $mongo = self::mongo();
        $limit = 1000;
        if ( property_exists($post,"limit") ) {
            $limit = $post->limit;
        }

        $query = [];
        if ( property_exists($post,"active") ) {
            $query["active"] = $post->active;
        }

        $projection = [
            'ad'=>1,
            'email'=>1,
            'ekfno'=>1,
            'ogrenci'=>1,
            'active'=>1,
            'dogum'=>1
        ];
        
        return Cast::toTable( $mongo->selectCollection("uye")->find($query,[ 'limit'=>$limit,'projection'=>$projection ]) );
    }

    public static function parola($post) {
        $mongo = self::mongo();
        $f = [
            "name" => $post->name,
            "password" => md5($post->password_old)
        ];
        $set = [
            '$set' => [
                'password' => md5($post->password_new)
            ]
        ];
        $num = $mongo->selectCollection("user")->updateOne($f,$set,[ "upsert"=>false ])->getMatchedCount();
        if ($num > 0) {            
            return true;
        } else {
            throw new Exception("Username or password is wrong");
        }
    }

    public static function yoklamalar($post) {
        $mongo = self::mongo();        
        
        $res = $mongo->selectCollection("uye")->aggregate([
            [ '$unwind' => '$keikolar' ],
            [ '$group' => [
                    '_id' => [ 'keikolar' => '$keikolar' ],
                    'sayi' => [ '$sum' => 1 ]
                ]
            ],
            [ '$sort' => [ '_id' => -1 ] ],
            [ '$limit' => 1000 ],
            [ '$project' => [
                    '_id' => 0,
                    'tarih' => '$_id.keikolar',
                    'sayi' => 1
                ] 
            ]
        ]);

        return Cast::toTable($res);

    }

    public static function yoklama_icindekiler($post) {
        $mongo = self::mongo();
        $projection = [
            '_id'=>1,
            'ad'=>1            
        ];
        $res = $mongo->selectCollection("uye")->find([ 'keikolar'=>  Cast::toISODate($post->tarih)  ],['projection'=>$projection]);
        return Cast::toTable($res);
    }

    public static function yoklama_disindakiler($post) {
        $mongo = self::mongo();
        $res = $mongo->selectCollection("uye")->find([
            'active'=>true,
            'keikolar' => [ '$nin' =>[ Cast::toISODate($post->tarih) ]  ]
        ],[
            'projection'=> [
                '_id'=>1,
                'ad'=>1
            ]
        ]);
        return Cast::toTable($res);
    }

    public static function yoklamaya_ekle($post) {
        $mongo = self::mongo();
        $mongo->selectCollection("uye")->updateOne([ "_id"=>Cast::toObjectId($post->_id) ],[ '$addToSet'=> [ 'keikolar' =>  Cast::toISODate($post->tarih) ]  ]);
        return true;
    }

    public static function yoklamadan_sil($post) {
        $mongo = self::mongo();
        $mongo->selectCollection("uye")->updateOne([ "_id"=>Cast::toObjectId($post->_id) ],[ '$pull'=> [ 'keikolar' =>  Cast::toISODate($post->tarih) ] ]);
        return true;
    }

    public static function img64($id) {
        $mongo = self::mongo();
        $data = $mongo->selectCollection("uye")->findOne([ "_id" => Cast::toObjectId($id) ]);
        if ( is_null($data)) {
            throw new Exception("Imge not found");
        }
        $b64 = $data["img"];
        
        if ( is_string($b64) ) {
            return $b64;
        } else {
            throw new Exception("Imge not found");
        }

    }
}