<?php

require "./db.php";

try {
    if ( isset($_GET["id"]) ) {
        $id = trim(filter_var($_GET["id"],FILTER_SANITIZE_STRING));        
        $bin = base64_decode(db::img64($id));
        $im = imageCreateFromString($bin);        
        header('Content-type:image/png');
        imagepng($im);
        imagedestroy($im);
    } else {
        throw new Exception("ID is requierd");
    }    
} catch (Exception $ex) {
    //print_r($_SERVER);
    $image = imagecreate(167, 222);
  
    // Set the background color of image
    $background_color = imagecolorallocate($image, 0, 153, 0);
  
    // Set the text color of image
    $text_color = imagecolorallocate($image, 255, 255, 255);

    imagestring($image, 5, 5, 5,  $ex->getMessage(), $text_color);
    header('Content-type:image/png');
    imagepng($image);
    imagedestroy($image);
}