<?php

namespace Web {
    class Page {

        public static int $time = 120;
        public static bool $useRnd = true;
        public static string $charset = "utf-8";
        public static string $COOKIE_NAME = "SUBUTAI";
        public static array $error_scope = [ E_ERROR, E_CORE_ERROR, E_USER_ERROR, E_COMPILE_ERROR ];


        public static function ErrorTemplate(string $htmlFile) : void {
            error_reporting(E_ALL);
            ini_set('display_errors', FALSE);
            ini_set('display_startup_errors', TRUE);
            $charset = self::$charset;
            $error_scope = self::$error_scope;

            $content = "";
            if ( file_exists($htmlFile) ) {
                $content = file_get_contents($htmlFile);
            } else {
                throw new \Exception("File not found / ".$htmlFile);
            }

            register_shutdown_function(function() use($content, $error_scope, $charset) {
                $er = error_get_last();  
                if (!is_null($er) && isset($er["type"]) && in_array($er["type"],$error_scope) ) {
                    $type = $er["type"];
                    $message = $er["message"];
                    $file = $er["file"];
                    $line = $er["line"];

                    if ( !headers_sent( $file, $line ) ) {
                        header("Content-Type: text/html; charset=".$charset);
                    }
                    
                    echo str_replace(["<!--TYPE-->","<!--MESSAGE-->","<!--FILE-->","<!--LINE-->"],[$type,$message,$file,$line],$content);
                                        
                }
            });
        }

        public static function ScriptTemplate(string $htmlFile,array $scripts,array $data = []) : void {
            self::data(self::$COOKIE_NAME,$data);
            $content = self::readContent($htmlFile);
            $charset = self::$charset;
            if ( !headers_sent( $file, $line ) ) {
                header("Content-Type: text/html; charset=$charset");
            }
            echo self::prepare($content,$scripts,self::$useRnd);
        }

        private static function prepare(string $content,array $scripts,bool $useRnd = true) : string {
            $rnd = "";
            if ($useRnd) {
                $rnd = "?".uniqid();    
            }
            $html_js = "";
            $html_css = "";
            for($i=0; $i<count($scripts); $i++) {
                $s = $scripts[$i];
                $arr = explode(".",$s);
                $ext = ( count($arr)>1 ? strtolower( end($arr) ) : "" );
                if ( $ext == "js" ) {
                    $html_js .= "<script src=\"$s$rnd\" type=\"module\"  ></script>";
                } elseif ( $ext == "css" ) {
                    $html_css .= "<link rel=\"stylesheet\" href=\"$s$rnd\" />";
                }
            }
            return str_replace(["<!--CSS-->","<!--JS-->"],[$html_css, $html_js],$content);
        }

        private static function data(string $cookie_name,array $data) {
            if ( !empty($data) ) {
                $op = array (
                    'expires' => time() +static::$time,
                    'path' => '/',                    
                    'secure' => true,
                    'samesite' => 'None' // None || Lax  || Strict
                );
                setcookie($cookie_name, json_encode( $data), $op); 
            }  else {
                if ( isset( $_COOKIE[$cookie_name] ) ) {
                    setcookie($cookie_name, "", time() - static::$time, "/");
                    unset($_COOKIE[$cookie_name]);
                }
            }     
        }

        private static function readContent(string $file) : string {
            if ( file_exists($file) ) {
                return \file_get_contents($file);
            } else {
                throw new \Exception("Template file not found / $file");
            }
        }
    }
}