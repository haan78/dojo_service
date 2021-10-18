<?php

namespace pgClass {

use Exception;

    class pgQueryException extends Exception {
        public function __construct($msg) { parent::__construct($msg); }
    }

    class pgQuery
    {
        private $conn = null;
        private string $sql = "";
        private array $params = [];

        public function __construct($conn) {
            if ( is_resource($conn) ) {
                if ( get_resource_type($conn)=="pgsql link" || get_resource_type($conn)=='pgsql link persistent') {
                    $this->conn = $conn;
                } else {
                    throw new pgException("Unknown resource type");
                }
            } elseif (is_string($conn)) {
                $this->conn = pgTool::connection($conn);
            } else {
                throw new pgException("Unknown resource type");
            }
        }

        public function param($value) : pgQuery {
            array_push( $this->params,$value );
            return $this;
        }

        public function clear():void {
            $this->params = [];
        }

        public function getLastSqlText() : string {
            return $this->sql;
        }

        private function generateSql(string $sqlTemp ) : string {

            $arr = explode("?", $sqlTemp);
            $result = "";

            if ( count($arr)-1 > count($this->params) ) {
                throw new pgException( (count($arr)-1)." parameter(s) are required but ".count($this->params)." given");
            }
            
            for ($i = 0; $i < count($arr) - 1; $i++) {
                $result .= $arr[$i] . pgTool::eveluate($this->params[$i]);
            }
            $result .= end($arr);

            $this->sql = $result;
            $this->clear();
          //  echo "\n".$this->sql."\n";
            return $this->sql;
        }

        private function exec(string $sql) {
            $result = pg_query($this->conn, $this->generateSql( $sql ) );
            if ($result !== false) {
                return $result;
            } else {
                throw new pgQueryException(pg_last_error($this->conn));
            }
        }

        public function list(string $sql,bool $assoc = true) {
            $list = [];
            $result = $this->exec($sql);
            if ( $assoc ) {
                while ($row = pg_fetch_assoc($result)) {
                    array_push($list,$row);
                }
            } else {
                while ($row = pg_fetch_row($result)) {
                    array_push($list,$row);
                }
            }
            pg_free_result($result);
            return $list;
        }

        public function page(string $sql,int $limit,int $page, &$maxPageNum, bool $assoc = true) : array {
            $sqlc = "SELECT COUNT(*) FROM ($sql) __Q__";
            $s = ($page - 1) * $limit;
            $sqlp = "SELECT * FROM ($sql) __Q__ LIMIT $limit OFFSET $s";
            $total = intval( $this->value($sqlc) );
            $maxPageNum = ceil( $total / $limit );
            return $this->list($sqlp,$assoc);
        }

        public function perform(string $sql) : void {
            $this->exec($sql);
        }

        public function row(string $sql,bool $assoc = true) {
            $result = $this->exec($sql);
            $row = null;
            if ( $assoc ) {
                $row = pg_fetch_assoc($result);
            } else {
                $row = pg_fetch_row($result);
            }
            pg_free_result($result);
            return $row;
        }

        public function value(string $sql) {
            $result = $this->row($sql,false);
            $value = null;
            if (!is_null($result) && isset($result[0]) ) {
                $value = $result[0];
            }            
            return $value;
        }

        public function __destruct() {
            if (!is_null($this->conn)) {
                pg_close($this->conn);
            }                     
        }
    }
}