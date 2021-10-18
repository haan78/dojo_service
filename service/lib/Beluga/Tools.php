<?php

namespace Beluga {
    trait Tools
    {
        private $session = [];
        public function keep(string $name,$value = null) {
            if ( is_null($value) ) {
                return $this->session[$name];
            } else {
                $this->session[$name] = $value;
                return true;
            }
        }

        public function clear() {
            $this->session = [];
        }
        
        public function delete_directory($dirname)
        {
            if (is_dir($dirname))
                $dir_handle = opendir($dirname);
            if (!$dir_handle)
                return false;
            while ($file = readdir($dir_handle)) {
                if ($file != "." && $file != "..") {
                    if (!is_dir($dirname . "/" . $file))
                        unlink($dirname . "/" . $file);
                    else
                        $this->delete_directory($dirname . '/' . $file);
                }
            }
            closedir($dir_handle);
            rmdir($dirname);
            return true;
        }
    }
}
