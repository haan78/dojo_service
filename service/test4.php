<?php

date_default_timezone_set('Europe/Istanbul');
ini_set('upload_max_filesize', '100M');
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);


require "./vendor/autoload.php";
require "./db.php";
require "./uploadlib.php";

printFile("mongodb://root:12345@mongodb", "dojo","dosya","61a009e632b61a1383569634");