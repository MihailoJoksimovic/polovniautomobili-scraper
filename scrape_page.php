<?php


require_once  'vendor/autoload.php';

use Goutte\Client;

$dsn = 'mysql:dbname=polovni_automobili;host=127.0.0.1';
$user = 'root';
$password = '100200300';

$dbh = new PDO($dsn, $user, $password);

$client = new Client();

$auto_id = 8264930;
$dbh->exec("SET CHARACTER SET utf8");;
while (true) {
    $query = $dbh->prepare("SELECT * FROM automobili WHERE status = 'not_processed' AND url LIKE '%punto%' LIMIT 1");

    $query->execute();

    $row = $query->fetch(PDO::FETCH_ASSOC);

    if (empty($row)) {
        echo "no more rows to process ... \n";

        exit(0);
    }

    $auto_id = $row['site_id'];

    $url = sprintf("http://polovniautomobili.com%s", $row['url']);

    echo "Crawling URL: $url \n";

    $crawler =  $client->request('GET', $url);

    try {
        $price = $crawler->filter('.price span')->first()->text();
    } catch (\Exception $e) {
        $dbh->query("UPDATE automobili SET status = 'processed' where site_id = $auto_id");

        continue;
    }


    echo "Price: $price\n";

    $price = $dbh->quote($price);

    $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'cena', value = $price");



    $crawler->filter('.basic-info')->each(function($node) use ($dbh, $auto_id) {
        /** @var $node Symfony\Component\DomCrawler\Crawler */

        $novoIliPolovno = $node->filter('li')->getNode(0)->textContent;
        $marka = $node->filter('li')->getNode(1)->textContent;
        $model = $node->filter('li')->getNode(2)->textContent;
        $godinaProizvodnje = $node->filter('li')->getNode(3)->textContent;
        $kilometraza = $node->filter('li')->getNode(4)->textContent;
        $tipVozila = $node->filter('li')->getNode(5)->textContent;
        $gorivo = $node->filter('li')->getNode(6)->textContent;
        $kubikaza = $node->filter('li')->getNode(7)->textContent;
        $kwks = $node->filter('li')->getNode(8)->textContent;
        $cenaFiksna = $node->filter('li')->getNode(9)->textContent;
        $zamena = $node->filter('li')->getNode(10)->textContent;

        $zamena = preg_replace('/\s+\s/', '', $zamena);

        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'novo_ili_polovno', value = ". $dbh->quote($novoIliPolovno));
        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'marka', value = ". $dbh->quote($marka));
        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'model', value = ". $dbh->quote($model));
        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'godinaProizvodnje', value = ". $dbh->quote($godinaProizvodnje));
        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'kilometraza', value = ". $dbh->quote($kilometraza));
        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'tipVozila', value = ". $dbh->quote($tipVozila));
        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'gorivo', value = ". $dbh->quote($gorivo));
        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'kubikaza', value = ". $dbh->quote($kubikaza));
        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'kw/ks', value = ". $dbh->quote($kwks));
        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'cenaFiksna', value = ". $dbh->quote($cenaFiksna));
        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = 'zamena', value = ". $dbh->quote($zamena));




    });

    $crawler->filter('.detailed-info li')->each(function($node) use ($dbh, $auto_id) {
        /** @var $node Symfony\Component\DomCrawler\Crawler */

        $name = $node->filter('span')->getNode(0)->textContent;
        $value = $node->filter('span')->getNode(1)->textContent;

        $name = $dbh->quote($name);
        $value = $dbh->quote($value);

        echo "$name: $value \n";

        $dbh->query("INSERT INTO automobili_meta SET auto_id = $auto_id, name = $name, value = $value");

    });

    $dbh->query("UPDATE automobili SET status = 'processed' where site_id = $auto_id");

    usleep(2000);

}

