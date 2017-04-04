<?php

/**
 * This file gives an example on how to search single purchase or multiple purchases from Siru Purchase status API.
 */

require_once('./configuration.php');
?>
<html>
    <head>
        <title>Siru purchase status demo</title>
    </head>

    <body>

        <h1>Search purchases using Siru API</h1>

        <p>
            This example exists only to demonstrate how to use Siru SDK. Do not use this example in your production environment.
        </p>

        <h2>Search by purchase UUID</h2>
        <form action="" method="GET">
            <input type="hidden" name="mode" value="uuid" />
            <label for="search-uuid">UUID</label>
            <input type="text" name="uuid" id="search-uuid" />
            <input type="submit" value="Search" />
        </form>

        <h2>Search by purchase reference</h2>
        <form action="" method="GET">
            <input type="hidden" name="mode" value="reference" />
            <label for="search-reference">UUID</label>
            <input type="text" name="reference" id="search-reference" />
            <input type="submit" value="Search" />
        </form>

        <h2>Search between dates</h2>
        <form action="" method="GET">
            <input type="hidden" name="mode" value="date" />
            <label for="search-from">Lower date and time limit</label>
            <input type="text" name="from" placeholder="YYYY-MM-DD HH:MM:SS" id="search-from" />
            <br/>
            <label for="search-to">Upper date and time limit</label>
            <input type="text" name="to" placeholder="YYYY-MM-DD HH:MM:SS" id="search-to" />
            <input type="submit" value="Search" />
        </form>

<?php

if(empty($_GET['mode']) == false) {

    echo "<hr/><h3>Results</h3>";

    // Create instance of Siru\Signature
    $signature = new Siru\Signature(constant('siru_merchant_id'), constant('siru_merchant_secret'));

    // Create instance of Siru\API which requires Siru\Signature as parameter
    $api = new Siru\API($signature);

    // Select staging environment (sandbox) or production for live environment
    if(constant('siru_use_staging_endpoint') == false) {
        $api->useProductionEndpoint();
    } else {
        $api->useStagingEndpoint();
    }

    // Get instance of Siru\API\PurchaseStatus which provides methos for searching transactions.
    $statusapi = $api->getPurchaseStatusApi();

    echo "<pre>";
    try {

        switch($_GET['mode']) {
            case 'uuid':
                $result = $statusapi->findPurchaseByUuid($_GET['uuid']);
                print_r($result);
                break;

            case 'reference':
                $result = $statusapi->findPurchasesByReference($_GET['reference']);
                print_r($result);
                break;

            case 'date':
                $result = $statusapi->findPurchasesByDateRange(new DateTime($_GET['from']), new DateTime($_GET['to']));
                print_r($result);
                break;
        }

    } catch(\Exception $e) {
        echo "Search resulted in " . get_class($e) . " exception:\n";
        echo $e->getMessage() . "\n";
    }

    echo "</pre>";
}
?>

    </body>
</html>
