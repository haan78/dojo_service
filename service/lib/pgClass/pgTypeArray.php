<?php

namespace pgClass {

    class pgTypeArray implements pgTypeBase {
        private array $list = [];
        private string $type; 
        public function __construct(array $list,string $type = "") {
            $this->list = $list;
            $this->type = $type;
        }

        public function load(string $data) : void {
            $str = trim($data,'{}');
            $arr = str_getcsv($str,",","\"");
            $this->list = [];
            for($i=0; $i<count($arr); $i++) {
                var_dump($arr[$i]);
                $v = pgTool::eveluate($arr[$i]);
                var_dump($v);
                array_push($this->list,$v);
            }
        }

        public function serialize() : string {
            return pgTool::eveluate($this->list).( $this->type != "" ? "::".$this->type."[]" : "" );
        }
    }
}