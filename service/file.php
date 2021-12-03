<?php
require_once "uploadlib.php";
require_once "./lib/Web/PathInfo.php";
use Web\PathInfo;
$module = ( PathInfo::item(1) ? PathInfo::item(1) : "show" );
if ( $module == "show" ) {
    UploadLib::show();
} elseif ( $module == "save" ) {
    UploadLib::save();
}
