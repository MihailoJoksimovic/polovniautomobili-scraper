<?php

require_once  'vendor/autoload.php';

use Goutte\Client;

$client = new Client();

$brand_id =109;

$crawler =  $client->request('GET', 'http://www.polovniautomobili.com/putnicka-vozila/pretraga?brand='.$brand_id.'&without_price=1&showOldNew=all');

$numOfAds = $crawler->filter('#numOfAds')->first()->text();
$perPage = 25;

$totalpages = ceil($numOfAds / 25);

echo "Total pages for brand $brand_id - $totalpages \n";

$dsn = 'mysql:dbname=polovni_automobili;host=127.0.0.1';
$user = 'root';
$password = '100200300';

$dbh = new PDO($dsn, $user, $password);

for ($page = 174; $page <= $totalpages; $page++) {
    $counter = 0;

    $url = "http://www.polovniautomobili.com/putnicka-vozila/pretraga?page={$page}&sort=renewDate_desc&brand={$brand_id}&city_distance=0&showOldNew=all&without_price=1";

    echo sprintf("--- Page: %d, URL: %s\n", $page, $url);

    $crawler =  $client->request('GET', $url);

    $crawler->filter('li.item.extend')->each(function ($node) use ($dbh, $brand_id) {
        /** @var $node Symfony\Component\DomCrawler\Crawler */
        $a = $node->filter(".itemtitle a");

        if ($a) {
            try {
                $url = $a->attr('href');
                $name = $a->text();

                $url_elements = explode("/", $url);
                $id = $url_elements[2];

                echo sprintf("URL: %s, ID: %s, Name: %s \n", $url, $id, $name);

                $name = $dbh->quote($name);
                $url = $dbh->quote($url);

                $dbh->query("INSERT INTO automobili (brand_id, site_id, name, url) VALUES ( $brand_id, $id, $name, $url) ");
            } catch (\Exception $e) {

            }

        }





    });

    sleep(3);
}


