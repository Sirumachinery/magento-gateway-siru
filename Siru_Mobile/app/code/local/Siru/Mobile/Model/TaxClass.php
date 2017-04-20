<?php

/**
 * Class Siru_Mobile_Model_purchaseCountry
 */
class Siru_Mobile_Model_TaxClass
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label' => Mage::helper('siru_mobile')->__('no tax')),
            array('value' => 1, 'label' => Mage::helper('siru_mobile')->__('tax class 1')),
            array('value' => 2, 'label' => Mage::helper('siru_mobile')->__('tax class 2')),
            array('value' => 3, 'label' => Mage::helper('siru_mobile')->__('tax class 3'))
        );
    }

}
