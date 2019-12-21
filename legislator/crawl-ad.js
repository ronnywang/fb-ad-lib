
var fs = require('fs');
var fb_ids = JSON.parse(fs.read('fb-id.json'));
var casper = require('casper').create(options);
var options = {
  verbose: true,
  logLevel: "debug"
};

casper.start("https://facebook.com");

var d = new Date;
var ymd = [
    ('0000' + d.getFullYear()).substr(-4),
    ('00' + (d.getMonth() + 1)).substr(-2),
    ('00' + (d.getDate())) .substr(-2),
].join('');
fs.makeDirectory("outputs/" + ymd);

var crawl_ids = [];
for (var url in fb_ids) {
    var page_id = fb_ids[url];
    if (!page_id) continue;
    if (fs.isFile("outputs/" + ymd + "/" + page_id + ".html")) continue;
    console.log("crawling " + page_id);
    var url ="https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=TW&impression_search_field=has_impressions_lifetime&view_all_page_id=" + page_id;
    (function(page_id, casper, fs){
        casper.thenOpen(url);
        casper.waitForSelector('div._7jv_, div._7gn3', function(resource) {
        
            fs.write("outputs/" + ymd + "/" + page_id + ".html", casper.getPageContent());
        }, function then() {
        }, function timeout() {
        }, 200000);
    })(page_id, casper, fs);
}

casper.run();
