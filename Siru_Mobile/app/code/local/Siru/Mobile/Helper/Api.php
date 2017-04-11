<?php

/**
 * Helper to retrieve Siru signature and api objects.
 */
class Siru_Mobile_Helper_Api extends Mage_Core_Helper_Abstract {

    /**
     * Include autoloader for vendor libraries.
     */
    public function __construct()
    {
        $code_path = Mage::getBaseDir('code');

        $path = $code_path . '/local/Siru/Mobile/vendor/autoload.php';

        require_once($path);
    }

    public function getSignature()
    {
        $data = Mage::getStoreConfig('payment/siru_mobile');

        $merchantId = $data['merchant_id'];
        $secret = $data['merchant_secret'];

        $signature = new \Siru\Signature($merchantId, $secret);

        return $signature;
    }

    public function getApi()
    {
        $data = Mage::getStoreConfig('payment/siru_mobile');

        $signature = $this->getSignature();
        $api = new \Siru\API($signature);

        // Use production endpoint if configured by admin
        $endPoint = $data['live_environment'];

        if (!$endPoint) {
            $api->useStagingEndpoint();
        }

        return $api;
    }

}
