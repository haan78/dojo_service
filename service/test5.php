<?php

date_default_timezone_set('Europe/Istanbul');
ini_set('upload_max_filesize', '100M');
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);


require "./vendor/autoload.php";
require "./db.php";
require "./uploadlib.php";

if (!empty($_POST)) {

    $message = "";
    $_id = "";
    try {
        $_id = upload("mongodb://root:12345@mongodb", "dojo");
    } catch (\Exception $ex) {
        $message = $ex->getMessage() . PHP_EOL .
            $ex->getFile() . PHP_EOL .
            get_class($ex) . PHP_EOL .
            $ex->getLine();
    }
}



?>
<!DOCTYPE html>
<html>

<body>
    <a href="test5.php">Refrefs</a>
    <form action="" method="post" enctype="multipart/form-data">
        Select image to upload:
        <input type="file" name="fileToUpload" id="fileToUpload"><br />
        <input type="text" name="_id" value="" placeholder="ID" /><br />
        <input type="text" name="ad" value="Ali" placeholder="Ad" /><br />
        <input type="text" name="email" value="haan1178@yahoo.com" placeholder="Email" /><br />
        <input type="text" name="ekfno" value="TR.00039" placeholder="EKFNO" /><br />
        <input type="text" name="cinsiyet" value="ERKEK" placeholder="ERKEK" /><br />
        <input type="text" name="dogum" value="2008-10-25T00:00:00.000Z" placeholder="Dogum" /><br />
        <input type="text" name="img" value="" placeholder="IMG" /><br />
        <input type="hidden" name="active" value="1" />
        <input type="submit" value="Gonder" name="submit">
    </form>
    <p>
    <pre style="color: red;">
    <?php echo $message; ?>

    </pre>
    </p>
</body>

</html>