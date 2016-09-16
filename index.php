<?php

require_once "vendor/autoload.php";

ob_implicit_flush();

$guzzle = new \GuzzleHttp\Client();

$dirname = __DIR__."/files/";
$dir = dir($dirname);
while($file = $dir->read()){

    if($file!="." && $file!=".."){
        echo date("G:i:s",microtime(true))." Начало проверки файла $file<br>";
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
        $response = json_decode(substr($response, strpos($response,',')+1),false, 512,JSON_BIGINT_AS_STRING);
		$response = $response->response->result;
        echo date("G:i:s",microtime(true))." Файл $file обработан, <a href=\"https://h5validator.appspot.com/adwords/result/{$response}\" target='_blank'>отчет</a><br>-------------------<br>";

    }

}
