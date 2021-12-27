<?php
if (PHP_SAPI != 'cli') {
    die("Works only CLI mode");
}

date_default_timezone_set('Europe/Istanbul');
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/lib/MongoTools/Tools.php";

use MongoTools\Cast;

function sendinblue($email, $id, $params)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.sendinblue.com/v3/smtp/email",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        //CURLOPT_POSTFIELDS =>"{ \"templateId\": $id, \"to\": [ { \"email\": \"$email\" } ], \"params\": { \"url\": \"$url\", \"isim\":\"$eczaci\" }}",
        CURLOPT_POSTFIELDS => "{ \"templateId\": $id, \"to\": [ { \"email\": \"$email\" } ], \"params\": " . json_encode($params) . "}",
        CURLOPT_HTTPHEADER => array(
            "api-key: " . $_ENV["SENDINBLUE_APIKEY"],
            "Content-Type: application/json"
        )
    ));

    $response = curl_exec($curl);
    $error = null;
    if ($response === FALSE) {
        $error = curl_error($curl);
    } else {
        $obj = json_decode($response, true);
        if (isset($obj["code"])) {
            $error = $obj["code"] . " / " . $obj["message"];
        } elseif (is_null($obj)) {
            $error = "E-Mail gonderimi basarisiz daha sonra tekrar deneyin($response)";
        }
    }
    curl_close($curl);

    if (!is_null($error)) {
        throw new \Exception($error);
    }
}

try {
    $dotenv = Dotenv\Dotenv::createImmutable("/etc", "dojoservice.env");
    $dotenv->load();
} catch (\Exception $ex) {
    die("Config File can't read");
}
var_dump($_ENV["MONGO_CONNECTION_STRING"]);
$mongo = new \MongoDB\Client($_ENV["MONGO_CONNECTION_STRING"]);

$query = [
    "active" => true,
    "email_activation" => false,
    "email" => [ '$ne' => null ]
    //,"eski_uye_id" => 101
];

if ( isset($argv[1]) ) {
    $query["_id"] = Cast::toObjectId($argv[1]);
}

$result = $mongo->selectDatabase("dojo")->selectCollection("uye")->find($query);

$it = new \IteratorIterator($result);
$it->rewind();
$ind = 1;
while ($doc = $it->current()) {
    $ad = (string)$doc["ad"];
    $_id = (string)$doc["_id"];
    $email = (string)$doc["email"];
    $message = "";
    $ead = [
        "email" => $email,
        "uye_id" => Cast::toObjectId($_id),
        "create_at" => new \MongoDB\BSON\UTCDateTime(),
        "update_at" => null
    ];    
    try {
        $res = $mongo->selectDatabase("dojo")->selectCollection("email_activation")->insertOne( $ead );
        $activationid = (string)$res->getInsertedId();
        sendinblue($email, 3, [
            "URL" => $_ENV["SERVICE_ROOT"]."/emailactivation/$activationid",
            "AD" => $ad
        ]);
        $message = "GONDERILDI";
    } catch (Exception $ex) {
        $message = $ex->getMessage();
    }
    sleep(2);
    echo "$ind | $_id | $ad | $email | $message".PHP_EOL;
    $it->next();
    $ind++;
}
