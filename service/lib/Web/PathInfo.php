<?php

namespace Web {
    class PathInfo {
        private static $arr = false;

        public static function remouteAddr() : string {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                return trim(explode(",",$_SERVER["HTTP_X_REAL_IP"])[0]);
            } elseif ( isset($_SERVER["HTTP_X_REAL_IP"]) ) {
                return trim($_SERVER["HTTP_X_REAL_IP"]);
            } elseif ( isset($_SERVER["REMOTE_ADDR"]) ) {
                return trim($_SERVER["REMOTE_ADDR"]);
            } else {
                return "";
            }
        }
        
        private static function parse() : array {
            if (isset($_SERVER["PATH_INFO"])) {
                $pi = explode("/",trim( $_SERVER["PATH_INFO"] ) );
                if (count($pi) >= 1 && $pi[0] == "") {
                    array_shift($pi);
                }
                return $pi;
            } else {
                return [];
            }
        }

        public static function count() : int {
            if (self::$arr === false) {
                self::$arr = self::parse();
            }
            return count(self::$arr);
        }

        public static function item(int $index) {
            if (self::$arr === false) {
                self::$arr = self::parse();
            }
            if ( isset(self::$arr[$index]) && !empty(self::$arr[$index]) ) {
                return trim(self::$arr[$index]);
            } else {
                return false;
            }
        }
    }
}