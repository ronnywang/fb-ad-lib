# fb-ad-lib

這邊放從 FB 廣告檔案庫抓取廣告相關資料的程式

* 程式說明
  * php fetch-data.php
    * 從 Ad Library API 抓取所有廣告詳情
    * 輸出結果： outputs/{YYYYMMDDHH}.json.gz
    * 輸出範例： fetch-data-sample.jsonl.gz
    * PS: 輸出結果的 ad_snapshot_url 內會包含你的 secret key ，請留意公開
    * API key 申請需要到 https://facebook.com/ID 認證身份
  * php legislator/crawl.php
    * 從 [立委參選人粉專列表](https://docs.google.com/spreadsheets/d/1RbFytnj_TAHXnW2v-QuAXw95UD6ULKgDN2639V8x-lo/edit#gid=1153115969) 確定參選名單抓出粉專網址列表，存入 legislator/list.csv
    * 將 legislator/list.csv 裡面的粉專網址抓出 fb 流水號，存入 legislator/fb-id.json
  * ./node_modules/.bin/casperjs crawl-ad.js
    * 先在 legislator 資料夾下 npm install 安裝好 casperjs
    * 透過 casperjs crawl-ad.js ，把 legislator/fb-id.json 列表內的粉專的廣告檔案庫頁抓下來，存到 legislator/outputs/{YYYYMMDD}/{FB_ID}.html
  * php legislator/parse.php
    * 將 crawl-ad.js 抓下來的廣告檔案庫 HTML ，parse 出 legislator/page.csv (粉專資料) 和 legislator/ad.csv (廣告資料)
  * php merge.php
    * 將 fetch-data.php 抓下來的完整檔案資料跟 parse.php 產生的 ad.csv 整合成一個檔案
    * 產生結果：https://gist.github.com/ronnywang/dd781d031501d69f70ddba6a2bf88d9b

* 程式碼授權
  * BSD License
