<?php

use Web\PathInfo;

require_once "db.php";
require_once "./lib/Web/PathInfo.php";
try {
    $_id = PathInfo::item(1);
    if (is_string($_id)) {
        db::validateEmail($_id);
    } else {
        throw new Exception("Bağlantı yanlış");
    }
    ?><span style="color: darkgreen; font-size: large; font-weight: bold;">Aktivasyon gerçekleşmiştir</span><?php      
} catch (Exception $ex) {
    ?><span style="color: red; font-size: large; font-weight: bold;"><?php echo $ex->getMessage(); ?></span><?php    
}