<?php
require_once "db.php";
require_once "validate.php";

class UploadLib {

    public static function save() {
        try {
            Validate::user();
            if ( !empty($_FILES) ) {
                $f = $_FILES[ array_key_first($_FILES) ];
                if ( $f["error"] == UPLOAD_ERR_OK ) {
                    $stream = fopen($f["tmp_name"], 'r');
                    if ( $stream !== FALSE ) {
                        rewind($stream);
                        $d = [
                            "ad" => $_POST["ad"],
                            "email" => $_POST["email"],
                            "ekfno" => $_POST["ekfno"],
                            "cinsiyet" => $_POST["cinsiyet"],
                            "dogum" => new  \MongoDB\BSON\UTCDateTime(strtotime($_POST["dogum"])*1000),
                            "active" => ($_POST["active"] == "1" ? true : false),
                            "img" => $_POST["img"]
                        ];
                        $_id = (!isset($_POST["_id"]) || is_null($_POST["_id"]) || empty($_POST["_id"]) ? null : $_POST["_id"]);
                        $link = db::link();
                        $fmeta = [
                            "time" => new \MongoDB\BSON\UTCDateTime(),
                            "file_type" => $f["type"],
                            "file_name" => $f["name"],
                            "info_text" => "Uye Foto",
                            "relation" => "uye.img",
                            "index" => 0
                        ];
                        $session = $link->startSession();
                        $session->startTransaction();
                        try {
                            $db = db::db($link);
                            $bucket = $db->selectGridFSBucket([
                                "bucketName" => "dosya"
                            ]);
                            if (!empty($d["img"])) {
                                $bucket->delete( new \MongoDB\BSON\ObjectId( $d["img"] ));
                            }
                            $bid = $bucket->uploadFromStream($f["name"], $stream, ["metadata" => $fmeta]);
                            $d["img"] = (string)$bid;
    
                            if (is_null($_id)) {
                                $result = $db->selectCollection("uye")->insertOne($d);
                                $_id = (string)$result->getInsertedId();
                            } else {
                                $db->selectCollection("uye")->updateOne([
                                    '_id' => new \MongoDB\BSON\ObjectId( $_id)
                                ], [
                                    '$set' => $d
                                ], [
                                    'upsert' => false
                                ]);
                            }
    
                            $session->commitTransaction();
                            echo "ID|$_id";
                        } catch (Exception $ex) {
                            $session->abortTransaction();
                            throw $ex;
                        }
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
            Validate::user();
            $db = db::mongo();
            $bucket = $db->selectGridFSBucket([
                "bucketName" => "dosya"
            ]);
            if ( isset($_GET["_id"]) ) {
                $id = new \MongoDB\BSON\ObjectId(trim($_GET["_id"]));
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