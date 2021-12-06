<?php

date_default_timezone_set('Europe/Istanbul');
ini_set('upload_max_filesize', '100M');
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);


$st="2021-12-05T21:18:05.931Z";
$dt = new DateTime($st);
$dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
echo $dt->format("d.m.Y H:i:s");