<?php
require_once __DIR__ . "/lib/MongoTools/Tools.php";
class db {
    private static $mongo = null;
    public static function mongo() {
        if (is_null(self::$mongo)) {
            $cs = $_ENV["MONGO_CONNECTION_STRING"];
            $dn = $_ENV["MONGO_DATABASE"];
            self::$mongo = ( \MongoTools\Cast::toClient($cs) )->selectDatabase($dn);
        }
        return self::$mongo;
    }

    public static function userFind(string $user,string $pass) {
        $mongo = self::mongo();
        $d = ["name" => $user, "password" => md5($pass)];
        $result = $mongo->selectCollection("user")->findOne($d);
        return $result;
    }
}