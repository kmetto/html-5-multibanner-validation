<?php

require_once "vendor/autoload.php";

$guzzle = new \GuzzleHttp\Client();

$dirname = __DIR__."/files/";
$dir = dir($dirname);
while($file = $dir->read()){

    if($file!="." && $file!=".."){
           $response =  $guzzle->request("POST", "https://h5validator.appspot.com/api/policy/adwords", [
               'multipart' => [
                   [
                       'name'     => "creative_bundle",
                       'contents' => fopen("./files/".$file, 'r'),
                       'headers'  => [
                           'Content-Type' => 'application/zip',
                           'Content-Disposition' => "form-data; name=creative_bundle; filename=$file;"
                       ]
                   ]
               ]
           ]);

        $response = $response->getBody()->getContents();
        $response = json_decode(substr($response, strpos($response,',')+1));

        echo "<a href=\"https://h5validator.appspot.com/adwords/result/{$response->response->result}\" target='_blank'>$file</a><br>";

    }

}
