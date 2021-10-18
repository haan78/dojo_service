<?php

namespace Web {

    use Exception;
    use stdClass;

    class Upload
    {
        private static function defaults(?stdClass $options)
        {
            return (object)[
                "mimeTypes" => (!is_null($options) && property_exists($options, "mimeTypes") ? (string)$options->mimeTypes : array("image/png", "image/jpeg", "image/gif", "application/pdf", "image/svg+xml", "image/webp")),
                "maxSize" => (!is_null($options) && property_exists($options, "maxSize") ? (int)$options->maxSize : (10 * 1024 * 1024)),
                "userFile" => (!is_null($options) && property_exists($options, "userFile") ? (string)$options->userFile : null),
                "fileLimit" => (!is_null($options) && property_exists($options, "fileLimit") ? (int)$options->fileLimit : 10),
                "onComplete" => (!is_null($options) && property_exists($options, "onComplete") ? $options->onComplete : null),
                "onNaming" => (!is_null($options) && property_exists($options, "onNaming") ? $options->onNaming : null),
                "onOutput"=> (!is_null($options) && property_exists($options, "onOutput") ? $options->onOutput : null),
                "onSave"=> (!is_null($options) && property_exists($options, "onSave") ? $options->onSave : null)               
            ];
        }

        private static function save(array &$list, $file, $folder, $op): void
        {
            $name = $file["name"];
            $pi = pathinfo($name);
            $base = $pi["basename"];
            $ext = (isset($pi["extension"]) ? $pi["extension"] : "");
            $fn = "";
            if ( is_null($op->onNaming) ) {
                $fn = substr(md5(openssl_random_pseudo_bytes(20)), -32) 
                    .md5(date("YmdHis")) 
                    . (!empty($ext) ? ".".strtolower($ext) : "" );
            } else {
                $fn = call_user_func_array( $op->onNaming, [$base,$ext] );
            }

            $target = "$folder/$fn";
            if (is_null($op->onSave) ) {
                if (!move_uploaded_file($file["tmp_name"], $target)) {
                    throw new Exception("The file $name couldn't move to the destination folder");
                }
            } else {
                call_user_func_array( $op->onSave, [$file["tmp_name"], $target] );
            }            
            array_push($list, $target);
        }

        private static function checkFiles(string $folder,stdClass $op) {
            if (!file_exists($folder)) {
                throw new Exception("There is no such kinda folder as $folder");
            } elseif (!is_writable($folder)) {
                throw new Exception("The $folder is not writable");
            } elseif (!is_null($op->onComplete) && !is_callable($op->onComplete)) {
                throw new Exception("The onComplete event must be callable or null");
            } elseif (!is_null($op->onNaming) && !is_callable($op->onNaming)) {
                throw new Exception("The onNaming event must be callable or null");
            } elseif (!is_null($op->onOutput) && !is_callable($op->onOutput)) {
                throw new Exception("The onOutput event must be callable or null");
            } elseif (!is_null($op->onSave) && !is_callable($op->onSave)) {
                throw new Exception("The onSave event must be callable or null");
            } elseif ( $op->fileLimit > count(  array_keys( $_FILES ) ) ) {
                throw new Exception("The file count is over the limit");
            }

            foreach($_FILES as $file) {
                $name = $file["name"];
                if ($file["error"] !== UPLOAD_ERR_OK) {
                    throw new Exception("The file $name upload has been failed");
                } elseif (!($file["size"] <= $op->maxSize)) {
                    throw new Exception("The file $name size is more than allowed");
                } elseif (!in_array($file["type"], $op->mimeTypes)) {
                    throw new Exception("The file $name mime type " . $file["type"] . " is not allowed");
                }
            }
        }

        public static function perform(string $folder, ?stdClass $options = null) : void {
            $op = self::defaults($options);

            $obj = new stdClass();
            $arr = [];
            try {
                self::checkFiles($folder,$op);

                if (!is_null($op->userFile)) {
                    if (!isset($_FILES[$op->userFile])) {
                        throw new Exception("There is no such kinda user file as " . $op->userFile);
                    } else {
                        $file = $_FILES[$op->userFile];
                        self::save($arr, $file, $folder, $op);
                    }
                } else {
                    foreach ($_FILES as $file) {
                        self::save($arr, $file, $folder, $op);
                    }
                }
                if ( !is_null($op->onComplete)) {
                    call_user_func_array( $op->onComplete, [ $arr ] );
                }
                $obj->success = true;
                $obj->data = $arr;
            } catch (Exception $ex) {
                for ($i = 0; $i < count($arr); $i++) {
                    unlink($arr[$i]);
                }
                $obj->success = false;
                $obj->message = $ex->getMessage();
            }
            self::output($obj,$op->onOutput);
        }

        private static function output(stdClass $obj,?callable $outputMethod) {
            if (is_null($outputMethod)) {
                header("Content-Type: application/json; charset=utf-8");
                echo json_encode($obj);
            } else {
                call_user_func_array( $outputMethod, [ $obj ] );
            }
        }
    }
}
