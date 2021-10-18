<?php

namespace Beluga {
    class Document
    {
        private $target;
        private Db $db;

        public function __construct(string $target, Db $db)
        {
            if (is_dir($target)) {
                $this->target = $target;
                $this->db = $db;
            } else {
                throw new \Beluga\Exception("Folder not found!");
            }
        }

        public function insert($data): Document
        {
            $str = json_encode($data);
            $fn = date('ymdHis') . "-" . uniqid();
            $file = $this->target . "/$fn.json";
            if (file_put_contents($file, $str, LOCK_EX) === FALSE) {
                throw new \Beluga\Exception("Data write error / $file");
            }
            $this->db->__setLastInsertId($fn);
            $this->db->__setCount(1);
            return $this;
        }

        public function delete($c = null): Document
        {

            $ids = array_keys($this->get($c));
            $j = 0;
            for ($i = 0; $i < count($ids); $i++) {
                $id = $ids[$i];
                $file = $this->target . "/$id.json";
                if (!unlink($file)) {
                    throw new \Beluga\Exception("The record could not delete / $file");
                }
                $j++;
            }
            $this->db->__setCount($i);
            return $this;
        }

        public function update($c): Document
        {
            $arr = $this->get($c);
            $i = 0;
            foreach ($arr as $id => $row) {
                $file = $this->target . "/" . $id . ".json";
                if (file_put_contents($file, \json_encode($row), LOCK_EX) === FALSE) {
                    throw new \Beluga\Exception("Data write error / $file");
                }
                $i++;
            }
            $this->db->__setCount($i);
            return $this;
        }

        public function list($c = null): array
        {
            return array_values($this->get($c));
        }

        public function first($c = null)
        {
            $arr = $this->get($c);
            $k = array_key_first($arr);
            return is_null($k) ? null : $arr[$k];
        }

        public function last($c = null)
        {
            $arr = $this->get($c);
            $k = array_key_last($arr);
            return is_null($k) ? null : $arr[$k];
        }

        public function get($c = null): array
        {
            $this->db->__setCount(0);
            $files = glob($this->target . "/*.json");
            $scope = new Scope($this->db);
            $j = 0;
            for ($i = 0; $i < count($files); $i++) {
                $file = $files[$i];
                $id = basename($file,".json");
                $data = json_decode(file_get_contents($file), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Beluga\Exception("Data read error / $file");
                }
                $scope->__setData($data, $id);
                if (isset($c)) {
                    $r = $c($scope);
                } else {
                    $scope->accept($data);
                }
                $j++;
            }
            $this->db->__setCount($j);
            return $scope->__getResult();
        }
    }
}
