<?php

/**
 * Class Siru_Sirumobilepaymentmethod_Helper_Data
 */
class Siru_Mobile_Helper_Data extends Mage_Core_Helper_Abstract{

    function getPaymentGatewayUrl()
    {
        return Mage::getUrl('sirumobilepaymentmethod/payment/gateway', array('_secure' => false));
    }

    public function  successAction()
    {

        exit('gdsdgs');
    }
}