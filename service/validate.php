<?php 
require_once "./db.php";
use Firebase\JWT\JWT;

class Validate {
    public static function user() : void {
        $headers = getallheaders();        
        if ( isset($headers["USER_DATA"]) ) {
            $obj = self::parseJwtDataString( $headers["USER_DATA"] );
            if ( property_exists($obj, 'user' ) && property_exists($obj, 'password' ) ) {
                $user = trim($obj->user);
                $pass = trim($obj->password);                                
                if ( db::userFind($user,$pass) ) {                    
                    return;
                } else {                
                    throw new Exception("Authontication has faild");
                }
            } else {
                var_dump($obj);
                throw new Exception("User and Password must send");
            }            
        } else {            
            throw new Exception("No user data");
        }
    }

    private static function parseJwtDataString ($jwt) : object {
        try {
            $key = $_ENV["JWT_KEY"];
            return  JWT::decode($jwt, $key, array('HS256'));
        } catch( Exception $ex ) {
            throw new Exception("Auth data can't parse / ".$ex->getMessage());
        }
    }
}