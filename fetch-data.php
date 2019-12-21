<?php

ini_set('memory_limit', '2G');
$access_token = '__access_token__';

$url = "https://graph.facebook.com/v4.0/ads_archive?fields=ad_creative_body,ad_creation_time,ad_creative_link_caption,ad_creative_link_description,ad_creative_link_title,ad_delivery_start_time,ad_delivery_stop_time,ad_snapshot_url,currency,demographic_distribution,funding_entity,impressions,page_id,page_name,region_distribution,spend";
$url .= '&' . "search_terms=''&ad_active_status=ALL&ad_type=POLITICAL_AND_ISSUE_ADS&ad_reached_countries=" . urlencode("['TW']") . "&access_token=" . urlencode($access_token);

$curl = curl_init();
date_default_timezone_set('Asia/Taipei');
$output = '';

$data = array();
while (true) {
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_Setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($curl, CURLOPT_URL, $url);
    $content = curl_exec($curl);

    $obj = json_decode($content);
    if (!$obj->data) {
        print_r($obj);
        break;
    }
    foreach ($obj->data as $data) {
        //unset($data->ad_snapshot_url);
        $output .= json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    }
    print_r($obj->paging);
    if (!$url = $obj->paging->next) {
        break;
    }
}

file_put_contents(__DIR__ . "/outputs/" . date("YmdH") . ".json.gz", gzencode($output));
