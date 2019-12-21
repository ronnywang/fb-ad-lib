<?php

date_default_timezone_set('Asia/Taipei');
$ad_fp = fopen(__DIR__ . '/ad.csv', 'w');
$page_fp = fopen(__DIR__ . '/page.csv', 'w');
fputcsv($page_fp, array(
    '選區', '參選人', '網址', 'FB_ID', '讚數', '藍勾勾', '建立日期', '管理員國家','改名次數',
));
fputcsv($ad_fp, array(
    '選區', '參選人', 'AD_ID', '登錄為政治廣告', '開始日期', '結束日期', '廣告內容', '圖片',
));

$fp = fopen(__DIR__ . '/list.csv', 'r');
fgetcsv($fp);
$map = array();
while ($rows = fgetcsv($fp)) {
    $map[$rows[3]] = $rows;
}
fclose($fp);

foreach (json_decode(file_get_contents(__DIR__ . "/fb-id.json")) as $url => $fb_id) {
    if (!$fb_id) continue;

    $files = glob(__DIR__ . "/outputs/2019*/{$fb_id}.html");
    sort($files);

    $page = new StdClass;
    $page->id = $fb_id;
    $page->ads = new StdClass;
    foreach ($files as $file) {
        $end_at = strtotime(explode('/', $file)[1]);
        $content = file_get_contents($file);
        $content = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $content);

        if (!preg_match('#,"pageInfo":({".*}),"publisherPlatforms"#', $content, $matches)) {
            error_log($file);
            error_log("pageInfo 再來不是 publisherPlatforms?");
            continue;
        }
        if (!$info = json_decode($matches[1])) {
            error_log($file);
            throw new Exception("pageInfo 再來不是 publisherPlatforms?");
        }
        $page->info = json_decode($matches[1]);

        $doc = new DOMDocument;
        @$doc->loadHTML($content);
        foreach ($doc->getElementsByTagName('input') as $input_dom) {
            if (preg_match('#^(Search by name|依名稱)#', $input_dom->getAttribute('placeholder'))) {
                $page->name = $input_dom->getAttribute('value');
            }
        }
        foreach ($doc->getElementsByTagName('div') as $div_dom) {

            if (strpos($div_dom->nodeValue, '編號：') === 0 or strpos($div_dom->nodeValue, 'ID: ') === 0) {
                $ad = new StdClass;
                if (strpos($div_dom->nodeValue, '編號：') === 0) {
                    $ad->id = explode('：', $div_dom->nodeValue)[1];
                } else {
                    $ad->id = explode(': ', $div_dom->nodeValue)[1];
                }

                $node = $div_dom;
                while ($node = $node->parentNode) {
                    if (preg_match('#<span>(\d+)年(\d+)月(\d+)日</span>開始刊登</div>#', $doc->saveHTML($node), $matches)) {
                        $ad->start = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
                        if (false !== strpos($doc->saveHTML($node->parentNode), '<div class="_7jw1">暫停</div>')) {
                            if (!property_exists($ad, 'end')) {
                                $ad->end = $end_at;
                            }
                        }
                        break;
                    } elseif (preg_match('#Started running on <span>([A-Za-z]+ \d+, \d+)</span>#', $doc->saveHTML($node), $matches)) {
                        $ad->start = strtotime($matches[1]);
                        if (false !== strpos($doc->saveHTML($node->parentNode), '<div class="_7jw1">Inactive</div>')) {
                            if (!property_exists($ad, 'end')) {
                                $ad->end = $end_at;
                            }
                        }
                        break;
                    } elseif (preg_match('#<span>([A-Za-z]+ \d+, \d+)</span> - <span>([A-Za-z]+ \d+, \d+)</span>#', $doc->saveHTML($node), $matches)) {
                        $ad->start = strtotime($matches[1]);
                        $ad->end = strtotime($matches[2]);
                        break;
                    }
                }

                $ad_type = null;
                if (!$ad->start) {
                    error_log($file);
                    throw new Exception("找不到開始刊登日期");
                }
                if (!property_exists($ad, 'end')) {
                    $ad->end = $end_at;
                }
                $node = $node->parentNode;
                $node = $node->parentNode;
                $node = $node->parentNode;
                $is_social_ad = false;
                foreach ($node->getElementsByTagName('div') as $ad_type_dom) {
                    if ($ad_type_dom->getAttribute('class') != '_8nox') {
                        continue;
                    }
                    if ($ad_type_dom->nodeValue == 'About social issues, elections or politics') {
                        $is_social_ad = true;
                        continue;
                    } elseif ($ad_type_dom->nodeValue == '有關社會議題、選舉或政治') {
                        $is_social_ad = true;
                        continue;

                    }
                    echo $ad_type_dom->nodeValue . "\n";
                    exit;
                }
                $ad->is_social_ad = $is_social_ad;
                foreach ($node->getElementsByTagName('div') as $content_div) {
                    if ($content_div->getAttribute('class') != '_7jyr') {
                        continue;
                    }
                    $ad->content = trim($content_div->nodeValue);

                    $image_div = $content_div;
                    while ($image_div = $image_div->nextSibling) {
                        if (in_array('_7jys', explode(' ', $image_div->getAttribute('class')))) {
                            $ad->image = $image_div->getAttribute('src');
                        } elseif (in_array('_8o0a', explode(' ', $image_div->getAttribute('class')))) {
                            if ($image_div->getElementsByTagName('video')->item(0)) {
                                $ad->image = $image_div->getElementsByTagName('video')->item(0)->getAttribute('poster');
                            }
                        }
                    }
                }
                foreach ($node->getElementsByTagName('img') as $image_div) {
                    if (in_array('_7jys', explode(' ', $image_div->getAttribute('class')))) {
                        $ad->image = $image_div->getAttribute('src');
                    }
                }
                if (!property_exists($ad, 'image')) {
                    error_log($ad->is_social_ad . ' https://www.facebook.com/ads/library/?id='.$ad->id . ' ' . $file);
                }
                $page->ads->{$ad->id} = $ad;
            }
        }
    }
    fputcsv($page_fp, array(
        $map[$url][0], // '選區'
        $map[$url][2], // '參選人'
        $url, // '網址'
        $fb_id, // 'FB_ID'
        $page->info->likes, // '讚數'
        $page->info->pageVerification, // '藍勾勾'
        date('Y-m-d', $page->info->pageCreationDate), // '建立日期'
        $page->info->pageAdminCountries, // '管理員國家',
        $page->info->pageNameChanges, // 改名次數
    ));

    if (get_object_vars($page->ads)) {
        foreach ($page->ads as $id => $ad) {
            fputcsv($ad_fp, array(
                $map[$url][0], // '選區'
                $map[$url][2], // '參選人'
                $id, // id
                $ad->is_social_ad ? '是' : '否',
                date('Y-m-d', $ad->start),
                date('Y-m-d', $ad->end),
                $ad->content,
                $ad->image,
            ));
        }
    }
}
fclose($ad_fp);
fclose($page_fp);
