<?php
date_default_timezone_set('Europe/Istanbul');
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

require_once "./vendor/autoload.php";
require_once "./lib/Web/PathInfo.php";
use Web\PathInfo;

try {
    $dotenv = Dotenv\Dotenv::createImmutable("/etc","dojoservice.env");
    $dotenv->load();
} catch ( \Exception $ex ) {
    die("Config File can't read");
}

$module = ( PathInfo::item(0) ? PathInfo::item(0) : "service" );

if ( $module == "service" ) {
    include "service.php";
    new service(1);
} elseif ($module == "file") {
    $method = PathInfo::item(1);
    include "upload.php";
    if ( $method == "show" ) {
        Upload::show();
    } elseif ( $method == "save" ) {
        Upload::save();
    } else {
        die("Method $method not supported");
    }
} else {
    die("Module not found $module");
}