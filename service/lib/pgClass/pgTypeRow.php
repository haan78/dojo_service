<?php

namespace pgClass {
    use ReflectionClass;

    class pgTypeRow
    {
        private final function getClassName()
        {
            $n = "";
            $s = "public";
            $classname = get_class($this);
            if ($n = strrpos($classname, '\\')) {
                $n = substr($classname, $n + 1);
            }

            $cl = (new ReflectionClass($this))->getConstants();
            if (isset($cl["SCHEMA"])) {
                $s = $cl["SCHEMA"];
            }
            if (isset($cl["NAME"])) {
                $n = $cl["NAME"];
            }
            return  "$s.$n";
        }

        public function load(string $data) : void {
            
        }

        public function serialize() : string
        {
            return pgTool::eveluate(get_object_vars($this)) . "::" . $this->getClassName();
        }
    }
}