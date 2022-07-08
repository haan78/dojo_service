<?php

namespace Web {

    use Exception;
    use ReflectionMethod;
    use Throwable;
    use Error;

class HttpResponseException extends Exception {
}

class HttpResponseAuthException extends Exception {
    private array $details;
    public function __construct($message = null,array $details = []) {
        parent::__construct($message, 0);
        $this->details = $details;
    }

    public function getDetails() : array {
        return $this->details;
    }
}

abstract class HttpResponse {

        //public static string $responsetypename = "";
        public static bool $SHOW_ERROR_DETAILS = false;

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
                        throw new HttpResponseException("Post data cannot be parsed into Json / $jle",201);
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
                    throw new HttpResponseException("Path info number $definer doesn't  exist",102);
                }
            } elseif ( is_string($definer) ) {
                $res = ( isset($_GET[$definer]) ? trim(htmlspecialchars($_GET[$definer],ENT_QUOTES)) : false );
                if (is_string($res) && !empty($res) ) {
                    return trim($res);
                } else {
                    throw new HttpResponseException("URL query name $definer doesn't exist",103);
                }
            } else {
                throw new HttpResponseException("Method definer must be path info number or URL query name",101);
            }
        }

        /*@override*/
        protected function log(string $method, $result,$post) : void { 
        }

        /*@override*/
        protected function logError($method,$result,$post) : void {

        }

        public function __construct($methodDefiner) {
            $post = null;
            $result = null;
            $methodName = null;
            $httpstatus = 500;
            ob_start();
            try {
                $methodName = $this->getMethodName($methodDefiner);
                if (method_exists($this, $methodName)) {
                    $rfm = new ReflectionMethod($this, $methodName);
                    if (($rfm->isPublic()) && (!$rfm->isConstructor()) && (!$rfm->isDestructor()) && (!$rfm->isStatic())) {
                        
                        $this->auth($methodName,function(string $message, array $details = []) {
                            throw new HttpResponseAuthException($message,$details);
                        });

                        $post = static::post();
                        $result = $rfm->invokeArgs($this, array($post));
                        $httpstatus = 200;
                    } else {
                        throw new HttpResponseException("Method is not callable",302);
                    }
                } else {
                    throw new HttpResponseException("Method $methodName dose not exist",301);
                } 
            } catch (Error $err) {
                $result = $this->doError($err,$httpstatus);
            } catch (Exception $ex) {
                $result = $this->doError($ex,$httpstatus);
            }
            $outputs = ob_get_contents();
            ob_end_clean();
            
            $this->doResponse($result,$httpstatus,$outputs);

            if ( !is_null($result) && $result["success"] ) {
                $this->log($methodName,$result,$post);
            } else {
                $this->logError($methodName,$result,$post);
            }            
        }

        abstract protected function auth(string $method, callable $abort) : void;
        abstract protected function doResponse($data,int $httpstatus,$outputs) : void;

        protected function doError(Throwable $ex, int &$httpstatus) : array {
            $data = [
                "message" => $ex->getMessage()                
            ];
            if ($ex instanceof HttpResponseAuthException) {                
                $data["class"] = get_class($ex);                
                $data["details"] = $ex->getDetails();                
            } elseif ($ex instanceof HttpResponseException) {
                $data["class"] = get_class($ex);
                $httpstatus = 400;
            } elseif (static::$SHOW_ERROR_DETAILS) {
                $data["code"] = $ex->getCode();
                $data["class"] = get_class($ex);
                $data["file"] = $ex->getFile();
                $data["line"] = $ex->getLine();
                $httpstatus = 500;
            } else {
                $data["code"] = $ex->getCode();
                $httpstatus = 500;
            }
            return $data;
        }
    }

    abstract class HttpResponseJson extends HttpResponse {
        public static int $JSON_FLAGS = 0; //JSON_PRETTY_PRINT
        protected function doResponse($data,int $httpstatus,$outputs) : void {
            http_response_code($httpstatus);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data, static::$JSON_FLAGS);
        }
        abstract protected function auth(string $method, callable $abort) : void;
    }
    abstract class HttpResponseJsonp extends HttpResponse {
        public static int $JSON_FLAGS = 0; //JSON_PRETTY_PRINT
        protected function doResponse($data,int $httpstatus,$outputs) : void {
            header('Content-Type: application/javascript; charset=utf-8');
            $fn = ( isset($_GET["fnc"]) ? trim(htmlspecialchars($_GET["fnc"],ENT_QUOTES)) : "HttpResponseJsonp");
            echo "if (typeof $fn === 'function' ) $fn( " . json_encode($data, static::$JSON_FLAGS) . ",$httpstatus ); else consloe.error('Function $fn not found.'); ";
        }
        abstract protected function auth(string $method, callable $abort) : void;
    }

}
