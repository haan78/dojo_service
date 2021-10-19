<?php
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
            self::$role = ( isset($res["role"]) ? $res["role"] : "USER" );
            return true;
        } else {
            return false;
        }        
    }
}