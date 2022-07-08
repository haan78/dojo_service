<?php

namespace Minmi {

    final class Container {
        private $routers = [];
        public function add(Router $router): Container {
            array_push($this->routers,$router);
            return $this;
        }

        public function execute(): bool {
            $result = false;
            for ($i=0; $i<count($this->routers); $i++) {
                if ( $this->routers[$i]->execute() )
                $result = true;
                break;
            }
            return $result;
        }
        
    }

    class MinmiExeption extends \Exception
    {
        private int $status = 500;
        public function __construct(string $message, int $status = 500)
        {
            $this->status = $status;
            parent::__construct($message, 0);
        }

        public function getStatus(): int
        {
            return $this->status;
        }
    }

    class Response
    {
        public ?\Exception $error = null;
        public $result = null;
        public string $debug = "";
        public int $status = 0;
    }

    class Request
    {
        private int $status = 200;
        private ?array $pathArray = null;
        private array $pathParams = [];
        private string $uripattern = "";

        public function __match(string $uri): bool
        {
            $params = [];
            $pia = $this->path();
            $ta = array_values(array_filter(explode("/", $uri)));
            $matched = true;
            if (count($pia) == count($ta)) {
                for ($i = 0; $i < count($pia); $i++) {
                    if (isset($ta[$i])) {
                        //echo $ta[$i];
                        if (preg_match("/\s*\@(\w+)\s*/", $ta[$i], $m)) {
                            $params[$m[1]] = trim($pia[$i]);
                        } elseif (preg_match("/\s*\#(\w+)\s*/", $ta[$i], $m) && preg_match("/^(\d+)$/", $pia[$i])) {
                            $params[$m[1]] = intval($pia[$i]);
                        } elseif ($ta[$i] != $pia[$i]) {
                            $matched = false;
                            break;
                        }
                    } else {
                        $matched = false;
                        break;
                    }
                }
            } else {
                $matched = false;
            }
            $this->pathParams = $params;
            $this->uripattern = $uri;
            return $matched;
        }

        public function getUriPattern():string {
            return $this->uripattern;
        }

        public function params(): array
        {
            return $this->pathParams;
        }

        public function setStatus(int $status)
        {
            $this->status = $status;
        }

        public function getStatus(): int
        {
            return $this->status;
        }

        public function agent(): string
        {
            return $_SERVER['HTTP_USER_AGENT'] ?? "";
        }

        public function remote(): string
        {
            return trim((explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["HTTP_X_REAL_IP"] ?? $_SERVER["REMOTE_ADDR"]))[0]);
        }

        public function json()
        {
            $PD = file_get_contents("php://input");
            if (!empty($PD)) { //Json has been sent
                $jd = json_decode($PD);
                $jle = json_last_error();
                if ($jle == JSON_ERROR_NONE) {
                    return $jd;
                } else {
                    throw new \Exception("Post data cannot be parsed into Json / $jle", 201);
                }
            } else {
                return null;
            }
        }

        public function path(): array
        {
            if (is_null($this->pathArray)) {
                $this->pathArray = array_values(array_filter(explode("/", ($_SERVER["PATH_INFO"] ?? ""))));
            }
            return $this->pathArray;
        }

        public function query(): object
        {
            $q = [];
            foreach ($_GET as $k => $v) {
                $q[$k] = htmlspecialchars($v);
            }
            return (object)$q;
        }

        public function raise(string $message, int $status = 500): void
        {
            throw new MinmiExeption($message, $status);
        }
    }

    abstract class Router
    {
        private string $prefix;
        private array $list;
        private $authmethod = null;

        public function __construct(string $prefix = "",?callable $authmethod = null)
        {
            $this->prefix = $prefix;
            $this->auth($authmethod);            
            $this->list = [];
        }

        public function auth(?callable $fnc) : Router {
            $this->authmethod = $fnc;
            return $this;
        }

        public function add(string $uri, callable $fnc, array $methods = []) : Router
        {
            array_push($this->list, [$uri, $fnc, $methods]);
            return $this;
        }

        public function execute(): bool
        {
            $method = $_SERVER['REQUEST_METHOD'];
            $request = new Request();
            for ($i = 0; $i < count($this->list); $i++) {
                $uri = $this->list[$i][0];
                $fnc = $this->list[$i][1];
                $methods = $this->list[$i][2];                
                if ((empty($methods) || in_array($method, $methods)) && $request->__match($this->prefix.$uri)) {
                    $response = new Response();                    
                    try {
                        ob_start();
                        if ( !is_null($this->authmethod) ) {
                            call_user_func_array($this->authmethod, [$request]);
                        }
                        $response->result = call_user_func_array($fnc, [$request]);
                        $response->debug = ob_get_contents();
                        ob_end_clean();                                               
                        $response->status = $request->getStatus();                       
                    } catch (MinmiExeption $ex) {
                        $response->status = $ex->getStatus();
                        $response->error = $ex;
                    } catch (\Exception $ex) {
                        $response->status = 500;
                        $response->error = $ex;
                    }                    
                    
                    $this->output($response);
                    return true;
                }
            }
            return false;
        }

        abstract protected function output(Response $response) : void;
    }

    class DefaultJsonRouter extends Router
    {
        public static int $JSON_FLAGS = 0;
        public static string $HEADER = 'Content-Type: application/json; charset=utf-8';


        protected function output(Response $response) : void
        {
            http_response_code($response->status);
            header(static::$HEADER);
            echo json_encode((!is_null($response->error) ? $response->error->getMessage() : $response->result), static::$JSON_FLAGS);
        }
    }
}
