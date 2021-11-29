<?php
function upload($connectionString,$dbname) {
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
    
    $link = new \MongoDB\Client($connectionString);
    $session = $link->startSession();
    $session->startTransaction();
    try {
        $db = $link->selectDatabase($dbname);
        foreach ($_FILES as $f) {
            if ($f["error"] == UPLOAD_ERR_OK) {
                $key = array_key_first($_FILES);

                $f = $_FILES[$key];
                $fmeta = [
                    "time" => new \MongoDB\BSON\UTCDateTime(),
                    "file_type" => $f["type"],
                    "file_name" => $f["name"],
                    "info_text" => "Uye Foto",
                    "relation" => "uye.img",
                    "index" => 0
                ];
                $stream = fopen($f["tmp_name"], 'r');
                if ($stream !== FALSE) {
                    //var_dump($stream);
                    rewind($stream);
                    $bucket = $db->selectGridFSBucket([
                        "bucketName" => "dosya"
                    ]);

                    if (!empty($d["img"])) {
                        $bucket->delete( new \MongoDB\BSON\ObjectId( $d["img"] ));
                    }
                    $bid = $bucket->uploadFromStream($f["name"], $stream, ["metadata" => $fmeta]);
                    $d["img"] = (string)$bid;
                } else {
                    throw new Exception("Upload has faild");
                }
            }
            break;
        }


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
        return $_id;
    } catch (\Exception $ex) {
        $session->abortTransaction();
        throw $ex;
    }
}

function printFile($connectionString,$dbname,$bucketName,$_id) {
    $link = new \MongoDB\Client($connectionString);
    $db = $link->selectDatabase($dbname);
    $bucket = $db->selectGridFSBucket([
        "bucketName" => $bucketName
    ]);
    $id = new \MongoDB\BSON\ObjectId($_id);
    $result = $bucket->findOne(["_id" => $id]);
    $destination = fopen('php://temp', 'w+b');
    $bucket->downloadToStream($id, $destination);
    header("Content-Type: " . $result->metadata->file_type);
    echo stream_get_contents($destination, -1, 0);
}