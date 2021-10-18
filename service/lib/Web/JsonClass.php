<?php

namespace Web {

    use Exception;
    use ReflectionMethod;
    use Throwable;
    use Error;

class JsonClassException extends Exception {
}

class JsonClassAuthException extends Exception {
    private array $details;
    public function __construct($message = null,array $details = []) {
        parent::__construct($message, 0);
        $this->details = $details;
    }

    public function getDetails() : array {
        return $this->details;
    }
}

class JsonClass {

        public static string $JSONP = "jsonp";
        public static int $JSON_FLAGS = 0;
        public static bool $SHOW_ERROR_DETAILS = false;
        public static bool $LOG_ONYL_SUCCESS_RESULTS = true;

        public static function debugMode() {
            self::$SHOW_ERROR_DETAILS = true;
            self:: $JSON_FLAGS = JSON_PRETTY_PRINT;
        }

        public static function post() {
            if (!empty($_POST)) {
                return (object)$_POST;
            } else {
                $PD = file_get_contents("php://input");
                if (!empty($PD)) { //Json has been sent
                    $jd = json_decode($PD);
                    $jle = json_last_error();
                    if ($jle == JSON_ERROR_NONE) {
                        return $jd;
                    } else {
                        throw new JsonClassException("Post data cannot be parsed into Json / $jle",201);
                    }
                } else {
                    return null;
                }
            }
        }
        
        public static function pathInfo(int $ind) {
            if (isset($_SERVER["PATH_INFO"])) {
                $pi = explode("/", trim($_SERVER["PATH_INFO"]));
                if (count($pi) >= 1 && $pi[0] == "") {
                    array_shift($pi);
                }
                if (isset($pi[$ind])) {
                    return trim($pi[$ind]);
                } elseif ($ind = -1) {
                    return count($pi);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        private function getMethodName($definer) : string {
            if ( is_int($definer) ) {
                $res = static::pathInfo($definer);
                if ( is_string($res) && !empty($res) ) {
                    return trim($res);
                } else {
                    throw new JsonClassException("Path info number $definer doesn't  exist",102);
                }
            } elseif ( is_string($definer) ) {
                $res = filter_input(INPUT_GET,  $definer, FILTER_SANITIZE_STRING);
                if (is_string($res) && !empty($res) ) {
                    return trim($res);
                } else {
                    throw new JsonClassException("URL query name $definer doesn't exist",103);
                }
            } else {
                throw new JsonClassException("Method definer must be path info number or URL query name",101);
            }
        }

        /*@override*/
        protected function log(string $method, $result,$post) : void { 
        }

        /*@override*/
        protected function logError($method,$result,$post) : void {

        }

        /*@override*/
        protected function auth(string $method, callable $abort) : void {
        }

        public function __construct($methodDefiner) {
            $post = null;
            $result = null;
            $methodName = null;
            try {
                $methodName = $this->getMethodName($methodDefiner);
                if (method_exists($this, $methodName)) {
                    $rfm = new ReflectionMethod($this, $methodName);
                    if (($rfm->isPublic()) && (!$rfm->isConstructor()) && (!$rfm->isDestructor()) && (!$rfm->isStatic())) {
                        $this->auth($methodName,function(string $message, array $details = []) {
                            throw new JsonClassAuthException($message,$details);
                        });
                        $post = static::post();
                        $result = $this->doSuccess($rfm->invokeArgs($this, array($post)));
                    } else {
                        throw new JsonClassException("Method is not callable",302);
                    }
                } else {
                    throw new JsonClassException("Method $methodName dose not exist",301);
                } 
            } catch (Error $err) {
                $result = $this->doError($err);
            } catch (Exception $ex) {
                $result = $this->doError($ex);
            }

            $this->doResponse($result);

            if ( !is_null($result) && $result["success"] ) {
                $this->log($methodName,$result,$post);
            } else {
                $this->logError($methodName,$result,$post);
            }            
        }

        protected function doResponse($json) : void {
            $p = filter_input(INPUT_GET,  self::$JSONP, FILTER_SANITIZE_STRING);
            if (($p != null) && ($p != false)) {
                header('Content-Type: application/javascript; charset=utf-8');
                echo "if (typeof $p === 'function' ) $p( " . json_encode($json, static::$JSON_FLAGS) . " ); else consloe.error('Function $p not found.'); ";
            } else {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($json, static::$JSON_FLAGS);
            }
        }

        protected function doSuccess($data) {
            return array("success" => true, "data" => $data);
        }

        protected function doError(Throwable $ex) {
            $data = [
                "message" => $ex->getMessage()                
            ];
            if ($ex instanceof JsonClassAuthException) {                
                $data["class"] = get_class($ex);                
                $data["details"] = $ex->getDetails();
            } elseif ($ex instanceof JsonClassException) {
                $data["class"] = get_class($ex);
            } elseif (static::$SHOW_ERROR_DETAILS) {
                $data["code"] = $ex->getCode();
                $data["class"] = get_class($ex);
                $data["file"] = $ex->getFile();
                $data["line"] = $ex->getLine();
            } else {
                $data["code"] = $ex->getCode();
            }
            return array("success" => false, "data" => $data);
        }
    }
}
