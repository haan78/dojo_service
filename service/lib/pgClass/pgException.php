<?php

namespace pgClass {
    use Exception;
    class pgException extends Exception {
        public function __construct($msg) { parent::__construct($msg); }
    }
}