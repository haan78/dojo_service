<?php
require_once "db.php";
require_once "validate.php";
require_once "./lib/Web/PathInfo.php";
use Web\PathInfo;

class Upload {

    public static function save() {
        try {
            Validate::user();
            //var_dump($_FILES);
            if ( !empty($_FILES) ) {
                $f = $_FILES[ array_key_first($_FILES) ];
                if ( $f["error"] == UPLOAD_ERR_OK ) {
                    $stream = fopen($f["tmp_name"], 'r');
                    if ( $stream !== FALSE ) {
                        rewind($stream);
                        $fmeta = [
                            "time" => new \MongoDB\BSON\UTCDateTime(),
                            "file_type" => mime_content_type($f["tmp_name"]),
                            "file_name" => $f["name"],
                            "info_text" => "Uye Foto",
                            "relation" => "uye.img",
                            "index" => 0
                        ];
                        $db = db::mongo();
                        $bucket = $db->selectGridFSBucket([
                            "bucketName" => "dosya"
                        ]);
                        $bid = $bucket->uploadFromStream($f["name"], $stream, ["metadata" => $fmeta]);
                        $_id = (string)$bid;
                        echo "ID|$_id";
                    } else {
                        throw new Exception("File can't read");
                    }
                } else {
                    throw new Exception("Upload has faild");
                }
                
            } else {
                throw new Exception("No upload file has found");
            }
        } catch ( Exception $ex ) {
            echo "ERROR|".$ex->getMessage();
        }
    }

    public static function show() {
        try {
            //Validate::user();
            $db = db::mongo();
            $bucket = $db->selectGridFSBucket([
                "bucketName" => "dosya"
            ]);
            $_id = ( PathInfo::item(2) ? PathInfo::item(2) : false );
            if ( $_id !==FALSE ) {
                $id = new \MongoDB\BSON\ObjectId(trim($_id));
                $result = $bucket->findOne(["_id" => $id]);
                if ( !is_null($result) ) {
                    $destination = fopen('php://temp', 'w+b');
                    $bucket->downloadToStream($id, $destination);
                    header("Content-Type: " . $result->metadata->file_type);
                    echo stream_get_contents($destination, -1, 0);
                } else {
                    throw new Exception("Data not found");
                }
            } else {
                throw new Exception("ID is required");
            } 
        } catch (Exception $ex) {
            //print_r($_SERVER);
            $image = imagecreate(167, 222);
  
            // Set the background color of image
            $background_color = imagecolorallocate($image, 0, 153, 0);
  
            // Set the text color of image
            $text_color = imagecolorallocate($image, 255, 255, 255);

            imagestring($image, 5, 5, 5,  $ex->getMessage(), $text_color);
            header('Content-type:image/png');
            imagepng($image);
            imagedestroy($image);
        }
    }
}