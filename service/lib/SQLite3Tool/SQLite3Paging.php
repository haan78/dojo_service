<?php

namespace SQLite3Tool {

    class SQLite3Paging {

        private $stmtQ = null;
        private $stmtC = null;
        private $sql;
        private $start = 0;
        private $limit = 10;
        private $conn = null;
        private $fields = [];
        public $values = [];

        public function __construct(\SQLite3 $conn, $sql, $start = 0, $limit = 10, $fields = []) {
            $this->sql = $sql;
            $this->start = $start;
            $this->limit = $limit;
            $this->conn = $conn;
            $this->fields = $fields;
            $this->refresh();
        }

        public function addField($name, $groupFnc) {
            $this->fields[$name] = $groupFnc;
        }

        private function generateGroupSql() {
            $sqlG = "SELECT COUNT(1) AS MAXROW ";
            foreach ($this->fields as $field => $fnc) {
                $sqlG .= ",$fnc AS $field";
            }
            $sqlG .= " FROM (" . $this->sql . ") QPAGING";
            return $sqlG;
        }

        private function refresh() {
            if (is_null($this->stmtC) || is_null($this->stmtQ)) {
                $this->stmtC = $this->conn->prepare($this->generateGroupSql());
                $this->stmtQ = $this->conn->prepare("SELECT * FROM ($this->sql) QPAGING LIMIT $this->start,$this->limit");
            }
        }

        public function bindParam($sql_param, &$param, int $type) {
            $this->stmtQ->bindParam($sql_param, $param, $type);
            $this->stmtC->bindParam($sql_param, $param, $type);
        }

        public function bindValue($sql_param, &$param, int $type) {
            $this->stmtQ->bindValue($sql_param, $param, $type);
            $this->stmtC->bindValue($sql_param, $param, $type);
        }

        public function result(&$numrow, $close = true, $arrayType = SQLITE3_ASSOC) {

            $rc = $this->stmtC->execute();
            $ac = $rc->fetchArray(SQLITE3_ASSOC);

            $rq = $this->stmtQ->execute();
            $aq = [];
            while ($r = $rq->fetchArray($arrayType)) {
                array_push($aq, $r);
            }

            if ($close) {
                $this->stmtC->close();
                $this->stmtQ->close();
            } else {
                $this->stmtC->reset();
                $this->stmtQ->reset();
            }
            $this->values = $ac;
            $numrow = $ac["MAXROW"];
            return $aq;
        }

    }

}