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
            array('value'=>'FI', 'label'=>'FI'),
            array('value'=>'UK', 'label'=>'UK')
        );
    }

}