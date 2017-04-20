<?php

/**
 * Class Siru_Mobile_Model_purchaseCountry
 */
class Siru_Mobile_Model_PurchaseCountry
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'FI', 'label' => Mage::helper('siru_mobile')->__('Finland'))
        );
    }

}
