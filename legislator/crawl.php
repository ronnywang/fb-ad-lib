<?php

$fb_id_cache = file_exists(__DIR__ . '/fb-id.json') ? json_decode(file_get_contents(__DIR__ . '/fb-id.json')) : new StdClass;


$url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vT9klmFUCak2M2sjHEUFm6xFZn_az8lOJbFAZyi41S7bgQMBfmZn8BWUIvJNYInlKyFQQrxyDxKbzeg/pub?gid=0&single=true&output=csv';
file_put_contents(__DIR__  . "/list.csv", file_get_contents($url));
$fp = fopen(__DIR__ . "/list.csv", 'r');
$columns = fgetcsv($fp);
while ($rows = fgetcsv($fp)) {
    $fb_url = trim($rows[3]);
    if ('' == trim($fb_url)) {
        continue;
    }
    if (property_exists($fb_id_cache, $fb_url) and $fb_id_cache->{$fb_url}) {
        continue;
    }

    $curl = curl_init($fb_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Chrome');
    $content = curl_exec($curl);
    if (preg_match('#<meta property="al:ios:url" content="fb://page/\?id=(\d+)"#', $content, $matches)) {
        $fb_id_cache->{$fb_url} = $matches[1];
    } else {
        error_log("{$fb_url} not a fb page?");
        $fb_id_cache->{$fb_url} = false;
    }
    file_put_contents(__DIR__ . '/fb-id.json', json_encode($fb_id_cache));
}
