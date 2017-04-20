<?php

/**
 * Class Siru_Mobile_Model_purchaseCountry
 */
class Siru_Mobile_Model_ServiceGroup
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label' => Mage::helper('siru_mobile')->__('Non-profit services')),
            array('value'=>2, 'label' => Mage::helper('siru_mobile')->__('Online services')),
            array('value'=>3, 'label' => Mage::helper('siru_mobile')->__('Entertainment services')),
            array('value'=>4, 'label' => Mage::helper('siru_mobile')->__('Adult entertainment services')),
        );
    }

}
