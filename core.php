<?php

require_once "vendor/autoload.php";

use Symfony\Component\DomCrawler\Crawler;
ob_implicit_flush();

$guzzle = new \GuzzleHttp\Client();

$dirname = __DIR__."/files";



function readDirectory($dirName){
    $dir = dir($dirName);
    $dirCont = [];
    while($file = $dir->read()){
        chdir($dirName);
        if($file!=='.' && $file!=='..'){
            if(!is_dir($file) && preg_match('/.*\.zip/', $file)){
                $dirCont[$file] = $dirName."/".$file;
            } elseif (is_dir($file)){
                $dirCont[$file] = readDirectory($dirName.'/'.$file);
            }
        }
    };

    return $dirCont;
}

function getResult($url){
  $html = file_get_contents($url);
  $crawler = new Crawler($html);
  $results = [];
  $crawler->filter("md-list-item")->each(function(Crawler $node, $i) use (&$results){
    if($node->attr('ga-category')=="Results"){
      $results[$node->attr('ga-label')] = preg_match("/.*pass.*/",$node->attr('class'))?true:false;
    }
  });

  return $results;
}

// echo '<details><summary></summary><p><pre>';
// print_r($tree = readDirectory($dirname));
// echo '</pre></p></details>';
//
$tree = readDirectory($dirname);

array_walk_recursive($tree, function($val, $key) use ($guzzle){
    echo "<hr><div class='line'><span class='date'>".date("G:i:s",microtime(true))."</span><span class='message'> Начало проверки файла ".$val."</span></div>";
    $response =  $guzzle->request("POST", "https://h5validator.appspot.com/api/policy/adwords", [
        'multipart' => [
            [
                'name'     => "creative_bundle",
                'contents' => fopen($val, 'r'),
                'headers'  => [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => "form-data; name=creative_bundle; filename=".basename($val).";"
                ]
            ]
        ]
    ]);

    $response = $response->getBody()->getContents();
    $response = json_decode(substr($response, strpos($response,',')+1), false, 512, JSON_BIGINT_AS_STRING);

    echo date("G:i:s",microtime(true))." Файл ".basename($val)." обработан, <a href=\"https://h5validator.appspot.com/adwords/result/{$response->response->result}\" target='_blank'>отчет</a><br><br>";
    $result = getResult("https://h5validator.appspot.com/adwords/result/{$response->response->result}");
    if(!empty($result)){
      foreach ($result as $check => $value) {
        $status = ($value)? "<span style='color:green'>OK</span>":"<span style='color:red'>ERROR</span>";
        echo $check." - ".$status."<br>";
      }

    }
});

echo "<br><br>"."Все баннеры проверены";
