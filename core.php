<?php
set_time_limit(0);
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

  $tree = readDirectory($dirname);
   array_walk_recursive($tree, function($val, $key) use ($guzzle){
   echo "<div class='item'><div class='line start'><span class='date'>".date("G:i:s",microtime(true))."</span><span class='message'> Начало проверки файла <code>".$val."</code></span></div>";
    try {
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


      echo "<div class='line end'><span class='date'>".date("G:i:s",microtime(true))."</span><span>Файл ".basename($val)." обработан</span></div>";
      $result = getResult("https://h5validator.appspot.com/adwords/result/{$response->response->result}");
      if(!empty($result)){
        foreach ($result as $check => $value) {
          $status = ($value)? "ok":"error";
          echo "<div class='check ".$status."'>".$check."</div>";

        }

      }
    } catch (Exception $e) {
      echo $e->getMessage();
    }
    echo "<div class='pad'><a class='result' href=\"https://h5validator.appspot.com/adwords/result/{$response->response->result}\" target='_blank'>Отчет</a></div></div>";
});

echo "<div class='final'>Все баннеры проверены</div>";

