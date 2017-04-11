<?php
/**
 * Insert merchantId and secret that you received from Siru Mobile in this file to test examples.
 */

DEFINE('siru_merchant_id', '');     # Put here your merchantId
DEFINE('siru_merchant_secret', ''); # Put here your merchant secret


# Do not edit anything beyond this point

error_reporting(E_ALL);
ini_set('display_errors', 1);

require('../vendor/autoload.php');

if(empty(constant('siru_merchant_id'))) {
    die("<strong>Error:</strong> You must first edit examples/configuration.php and enter your merchant id.");
}
if(empty(constant('siru_merchant_secret'))) {
    die("<strong>Error:</strong> You must first edit examples/configuration.php and enter your merchant secret.");
}
