<?php

namespace pgClass {
    interface pgTypeBase {
        const SCHEMA = "public";
        const NAME = NULL;
        public function serialize() : string;
        public function load(string $data) : void;
    }
}