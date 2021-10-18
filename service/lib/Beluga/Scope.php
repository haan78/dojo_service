<?php
namespace Beluga {

    class Scope {
        private Db $db;

        private $data;
        private string $id;
        private array $resultset = [];
        public function __construct(Db $db)
        {
            $this->db = $db;
        }

        public function db() : Db {
            return $this->db;
        }

        public function keep(string $name,$value = null) {
            return $this->db->keep($name,$value);
        }

        public function clear() {
            $this->db->clear();
        }

        public function data()  {
            return $this->data;
        }

        public function accept($obj) {            
            $this->resultset[$this->id] = $obj;
        }

        public function __setData($data,string $id) {
            $this->data = $data;
            $this->id = $id;
        }

        public function __getResult() {
            return $this->resultset;
        }
    }
}