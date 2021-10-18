<?php

namespace pgClass {
    class pgTypeJson implements pgTypeBase {
        private $data;
        private bool $isJsonb;
        public function __construct($data,bool $jsonb = false) {
            $this->data = $data;
            $this->isJsonb = $jsonb;
        }

        public function load(string $data) : void {
            $this->data = json_decode($data);
        }

        public function serialize() : string {
            $json = json_encode($this->data);
            $jle = json_last_error();
            if ( $jle !== JSON_ERROR_NONE ) {
                throw new pgException("Json Error Code is ".$jle);
            }
            return "'".$json."'::".( $this->isJsonb ? 'jsonb' : 'json' );
        }
        
    }
}