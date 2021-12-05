<?php 
define("SENDINBLUE_APIKEY","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");

function sendinblue($email,$id,$params) {
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
        CURLOPT_POSTFIELDS =>"{ \"templateId\": $id, \"to\": [ { \"email\": \"$email\" } ], \"params\": ".json_encode($params)."}",
        CURLOPT_HTTPHEADER => array(
            "api-key: ".SENDINBLUE_APIKEY,
            "Content-Type: application/json"
        )
    ));

    $response = curl_exec($curl);
    $error = null;
    if ( $response === FALSE ) {
        $error = curl_error($curl);
    } else {
        $obj = json_decode($response,true);
        if ( isset($obj["code"]) ) {
            $error = $obj["code"]." / ".$obj["message"];
        } elseif (is_null($obj)) {
            $error = "E-Mail gonderimi basarisiz daha sonra tekrar deneyin($response)";
        }
    }
    curl_close($curl);

    if (!is_null($error)) {
        throw new \Exception($error);
    }
}

sendinblue("handegungordu@gmail.com",1,[ "AKTIVASYON_URL"=> "http://www.ankarakendo.com","UYE_AD"=>"Hande Öztürk" ]);

