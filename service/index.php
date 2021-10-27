<?php
date_default_timezone_set('Europe/Istanbul');
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

require "./vendor/autoload.php";
require "./lib/Web/PathInfo.php";
use Web\PathInfo;

try {
    $dotenv = Dotenv\Dotenv::createImmutable("/etc","dojoservice.env");
    $dotenv->load();
} catch ( \Exception $ex ) {
    die("Config File can't read");
}

$module = ( PathInfo::item(0) ? PathInfo::item(0) : "service" );
$mfile = __DIR__."/$module.php";

if ( file_exists( $mfile )  ) {
    include $mfile;
} else {
    die("File not found $mfile");
}