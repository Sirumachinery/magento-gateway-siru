<?php

/**
 * This file gives an example on how to search single purchase or multiple purchases from Siru Purchase status API.
 */

require_once('./configuration.php');

// Create instance of Siru\Signature
$signature = new Siru\Signature(constant('siru_merchant_id'), constant('siru_merchant_secret'));

// Create instance of Siru\API which requires Siru\Signature as parameter
$api = new Siru\API($signature);

// Select request default values and select staging environment (sandbox)
// Change these values as required
$api->setDefaults([
    'purchaseCountry' => 'FI',
    'variant' => 'variant1',
    'taxClass' => 3,
    'serviceGroup' => 2,
    'redirectAfterSuccess' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['DOCUMENT_URI'],
    'redirectAfterFailure' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['DOCUMENT_URI'],
    'redirectAfterCancel' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['DOCUMENT_URI'],
])->useStagingEndpoint();

?>

<html>
    <head>
        <title>Siru payment example</title>
    </head>

    <body>

        <h1>Create new payment to Siru API</h1>

        <p>
            Code example on how to create new payment with Siru Mobile using the SDK.
        </p>

<?php

// This block is run if user is redirected here from Siru payment page
if(isset($_GET['siru_event'])) {
    echo "<p>";

    // Validate signature sent by Siru
    $isValidEvent = $signature->isNotificationAuthentic($_GET);
    if($isValidEvent === false) {
        echo 'Response is <strong style="color: #f77">INVALID</strong><br/>';
    } else {
        echo 'Response is <strong style="color: #7f7">VALID</strong><br/>';
    }

    // Here we could update payment status and users account accordingly
    switch($_GET['siru_event']) {
        case "success":
            echo "Payment was successful";
            break;
        case "failure":
            echo "Payment failed";
            break;
        case "cancel":
            echo "User canceled payment";
            break;
    }

    echo "</p>";
}

// This is the payment form
if(empty($_POST)) {

    echo <<<EOD
<form action="" method="post">
    <label for="basePrice">Payment amount</label>
    <input type="text" name="basePrice" id="basePrice" value="5.00" /><br/>
    <label for="customerNumber">Phone number</label>
    <input type="text" name="customerNumber" id="customerNumber"/><br/>
    <input type="submit" value="Proceed"/>
</form>
EOD;

}

// This block is run when user submits payment form
if(empty($_POST) == false) {

    try {

        // Get instance of Siru\API\PurchaseStatus
        $paymentapi = $api->getPaymentApi();

        // Take values from form and create payment
        $paymentData = $paymentapi
            ->set('basePrice', $_POST['basePrice'])
            ->set('customerNumber', $_POST['customerNumber'])
            ->createPayment();

        # $paymentData['uuid'] you should link to payment in your own database
        
        header('location: ' . $paymentData['redirect']);
        exit;

    } catch(\Exception $e) {
        echo "<h2>An error occured while creating payment</h2>";
        echo "<pre>";
        echo "Exception: " . get_class($e) . "\n";
        echo $e->getMessage() . "\n";
        if($e instanceof Siru\Exception\ApiException) {
            echo "Error messages from API:\n";
            print_r($e->getErrorStack());
        }
        echo "</pre>";
    }
}
?>

    </body>
</html>
