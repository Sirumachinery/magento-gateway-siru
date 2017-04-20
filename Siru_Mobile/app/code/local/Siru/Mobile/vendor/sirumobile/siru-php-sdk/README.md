# Siru Payment Gateway PHP SDK

Siru Payment Gateway Software development kit for PHP 5.6+.

## Installation

The SDK is available at [Packagist](https://packagist.org) and it is recommended that you use [composer](http://getcomposer.org) to include it in your project. The SDK depends on GuzzleHttp for making HTTP requests.

You can include SDK by executing command:
```sh
$ composer require sirumobile/siru-php-sdk
```
or by adding the following lines to your composer.json file:
```json
{
    "require": {
        "sirumobile/siru-php-sdk": "dev-master"
    }
}
```

## Usage

To get started, you need your merchantId and merchant secret. If you don't have your credentials, 
contact [Siru Mobile](https://sirumobile.com) to discuss which payment methods are available to you and we will send you your sandbox credentials.

Then go through our [API documentation](https://sirumobile.com/developers) to learn more about each API and message payloads.

## Example

Here is a simple example on how to create new transaction and redirect user to Siru payment flow:

```PHP
# web/checkout.php

require_once('../vendor/autoload.php');

/**
 * Siru\Signature is used to sign outgoing messages and verify
 * responses from API. Replace $merchantId and $secret with your own credentials.
 */
$signature = new \Siru\Signature($merchantId, $secret);

/**
 * Siru\API is used to retrieve API specific classes and setting default values
 * for outgoing requests. It requires instance of Siru\Signature as parameter.
 */
$api = new \Siru\API($signature);

// Select sandbox environment (default)
$api->useStagingEndpoint();

// You can set default values for all payment requests (not required)
$api->setDefaults([
  'variant' => 'variant4',
  'purchaseCountry' => 'GB'
]);

// Create payment
try {

  $transaction = $api->getPaymentApi()
    ->set('basePrice', '5.00')
    ->set('redirectAfterSuccess', 'https://my-shop.com/checkout/success')
    ->set('redirectAfterFailure', 'https://my-shop.com/checkout/failure')
    ->set('redirectAfterCancel', 'https://my-shop.com/checkout/cancel')
    ->set('customerNumber', '0401234567')
    ->set('title', 'Concert ticket')
    ->set('description', 'Concert ticket to see an awesome band live')
    ->createPayment();
  
  header('location: ' . $transaction['redirect']);
  exit();

} catch(\Siru\Exception\InvalidResponseException $e) {
  echo "Unable to contact Payment API."

} catch(\Siru\Exception\ApiException $e) {
  echo "API reported following errors:<br />";
  foreach($e->getErrorStack() as $error) {
    echo $error . "<br />";
  }
}
```

On your redirectAfter* URLs you will need to verify that user has actually arrived from Siru Mobile payment flow and all parameters are authentic:

```PHP
# /web/checkout/success.php, failure.php or cancel.php
$signature = new \Siru\Signature($merchantId, $secret);

if(isset($_GET['siru_event']) == true) {
  if($signature->isNotificationAuthentic($_GET)) {
    // User was redirected from Siru payment page and query parameters are authentic
  }
}
```

It is recommended that you also setup a callback URL using notifyAfterSuccess, notifyAfterFailure and notifyAfterCancel fields where Siru will automatically send notification when payment status changes. This allows you to complete the transaction even if user fails to return to your checkout page for example due to a network failure.

```PHP
// /web/checkout/callback.php
$signature = new \Siru\Signature($merchantId, $secret);

$entityBody = file_get_contents('php://input');
$entityBodyAsJson = json_decode($entityBody, true);

if($signature->isNotificationAuthentic($entityBodyAsJson)) {
  // Notification was sent by Siru Mobile and is authentic
}
```

You can also use `\Siru\Signature` as standalone to create signature for your own code.

```PHP
/**
 * Imaginary example on calculating Signature without using \Siru\API.
 */
$paymentRequestFields = [
  // ... required fields as described in API documentation.
];

$hash = $signature->createMessageSignature($paymentRequestFields, [], Signature::FILTER_EMPTY | Signature::SORT_FIELDS);
$paymentRequestFields['signature'] = $hash;
$paymentRequestJson = json_encode($paymentRequestFields);

// Send request using what ever HTTP
$response = $myHttpClient->send('https://staging.sirumobile.com', 'POST', $paymentRequestJson);

// Then you would check API response status, parse the JSON string in response body
// and redirect user to the payment page.
```

## API documentation

API documentation is available [here](https://sirumobile.com/developers).
