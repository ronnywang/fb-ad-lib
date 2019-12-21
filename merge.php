<?php

ini_set('memory_limit', '2g');
$fp = fopen(__DIR__ . '/legislator/ad.csv', 'r');
$columns = fgetcsv($fp);
$ads = array();
while ($row = fgetcsv($fp)) {
    $values = array_combine($columns, $row);
    $ads[$values['AD_ID']] = $values;
}

$files = glob(__DIR__ . "/outputs/*.json.gz");
rsort($files);
$file = array_shift($files);
$fp = gzopen($file, 'r');
$content_pool = array();

while ($line = fgets($fp)) {
    $obj = json_decode(trim($line));
    $url = $obj->ad_snapshot_url;
    unset($obj->ad_snapshot_url);
    preg_match('#id=(\d+)#', $url, $matches);
    $id = $matches[1];
    $obj->ad_id = $id;
    $content_id = $obj->page_id . '-' . crc32($obj->ad_creative_body) . '-' . crc32($obj->ad_creative_link_caption);
    if (!array_key_Exists($content_id, $content_pool)) {
        $content_pool[$content_id] = array();
    }
    $content_pool[$content_id][$id] = json_encode($obj);
}
fclose($fp);

$fp = gzopen($file, 'r');
while ($line = fgets($fp)) {
    $obj = json_decode(trim($line));
    $url = $obj->ad_snapshot_url;
    unset($obj->ad_snapshot_url);
    preg_match('#id=(\d+)#', $url, $matches);
    $id = $matches[1];
    $content_id = $obj->page_id . '-' . crc32($obj->ad_creative_body) . '-' . crc32($obj->ad_creative_link_caption);


    if (!array_key_exists($id, $ads)) {
        continue;
    }

    foreach ($content_pool[$content_id] as $line) {
        $obj = json_decode($line);
        $ads[$obj->ad_id] = $ads[$id];
        $ads[$obj->ad_id]['AD_ID'] = $obj->ad_id;
        $ads[$obj->ad_id]['廣告詳情'] = $obj;
    }
}

$fp = gzopen(__DIR__ . '/output.gz', 'w');
foreach ($ads as $ad) {
    fputs($fp, json_encode($ad, JSON_UNESCAPED_UNICODE) . "\n");
}
fclose($fp);
