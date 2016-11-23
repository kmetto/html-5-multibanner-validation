<?php
set_time_limit(0);
require_once "vendor/autoload.php";

use Symfony\Component\DomCrawler\Crawler;

ob_implicit_flush();

$guzzle = new \GuzzleHttp\Client();

$dirname = __DIR__ . "/files";

function readDirectory($dirName)
{
    $dir = dir($dirName);
    $dirCont = [];
    while ($file = $dir->read()) {
        chdir($dirName);
        if ($file !== '.' && $file !== '..') {
            if (!is_dir($file) && preg_match('/.*\.zip/', $file)) {
                $dirCont[$file] = $dirName . "/" . $file;
            } elseif (is_dir($file)) {
                $dirCont[$file] = readDirectory($dirName . '/' . $file);
            }
        }
    };

    return $dirCont;
}

function getResult($url)
{
    echo "<iframe width='100%' height='300px' src='$url' frameBorder='0'></iframe>";
}

$tree = readDirectory($dirname);
array_walk_recursive($tree, function ($val, $key) use ($guzzle) {
    echo "<div class='item'><div class='line start'><span class='date'>" . date("G:i:s",
            microtime(true)) . "</span><span class='message'> Начало проверки файла <code>" . $val . "</code></span></div>";
    try {
        $response = $guzzle->request("POST", "https://h5validator.appspot.com/api/policy/adwords", [
            'multipart' => [
                [
                    'name' => "creative_bundle",
                    'contents' => fopen($val, 'r'),
                    'headers' => [
                        'Content-Type' => 'application/zip',
                        'Content-Disposition' => "form-data; name=creative_bundle; filename=" . basename($val) . ";"
                    ]
                ]
            ]
        ]);

        $response = $response->getBody()->getContents();

        $response = json_decode(substr($response, strpos($response, ',') + 1), false, 512, JSON_BIGINT_AS_STRING);

        https://h5validator.appspot.com/api/policy/adwords/result/6554295996514304

        $response = file_get_contents("https://h5validator.appspot.com/api/policy/adwords/result/{$response->response->result}");
        $report = json_decode(substr($response, strpos($response, ',') + 1), false, 512, JSON_BIGINT_AS_STRING);

        echo "<div class='line end'><span class='date'>" . date("G:i:s",
                microtime(true)) . "</span><span>Файл " . basename($val) . " обработан</span></div>";

        foreach ($report->response->result->validation_case_results as $key => $result) {
            echo "<div class='check " . $result->status . "'>" . $result->name . "</div>";
        }

        echo "<iframe frameborder=\"0\" width='{$report->response->result->previews[0]->width}' height='{$report->response->result->previews[0]->height}' src='https:{$report->response->result->previews[0]->src}'></iframe>";
    } catch (Exception $e) {
        echo $e->getMessage();
    }
});

echo "<div class='final'>Все баннеры проверены</div>";

