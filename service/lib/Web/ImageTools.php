<?php

namespace Web {

    class ImageTools {

        public static function resize($filename, int $width, int $height) {
            list($w, $h) = getimagesize($filename);
            $rate = sqrt(pow($width, 2) + pow($height, 2)) / sqrt(pow($w, 2) + pow($h, 2));
            $nw = $rate * $w;
            $nh = $rate * $h;
            $target = imagecreatetruecolor($nw, $nh);
            $source = self::imageCreateFromFile($filename,$type);
            return imagecopyresized($target, $source, 0, 0, 0, 0, $nw, $nh, $w, $h);
        }

        private static function imageCreateFromFile(string $fileName, string &$type) {
            $type = mime_content_type($fileName);
            if ($type == "image/png") {
                return @imagecreatefrompng($fileName);
            } elseif($type == "image/jpeg") {
                return @imagecreatefromjpeg($fileName);
            } elseif($type == "image/gif") {
                return @imagecreatefromgif($fileName);
            } elseif($type == "image/bmp") {
                return @imagecreatefrombmp($fileName);
            } elseif($type == "image/webp") {
                return @imagecreatefromwebp($fileName);
            } else {
                return false;
            }
        }

        private static function image(string $type, $im) : void {
            if ($type == "image/png") {
                imagepng($im);
            } elseif($type == "image/jpeg") {
                imagejpeg($im);
            } elseif($type == "image/gif") {
                imagegif($im);
            } elseif($type == "image/bmp") {
                imagebmp($im);
            } elseif($type == "image/webp") {
                imagewebp($im);
            }
        }

        public static function show($fileName) {            
            $img = false;
            if (file_exists($fileName)) {
                $img = self::imageCreateFromFile($fileName,$type);
                if ($img !==FALSE) {
                    header('Content-Type: image/jpeg');
                    self::image($type,$img);
                    imagedestroy($img);
                }
            }
        }

    }

}