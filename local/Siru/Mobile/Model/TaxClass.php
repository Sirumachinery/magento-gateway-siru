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
                array('value'=>0, 'label'=>0),
                array('value'=>1, 'label'=>1),
                array('value'=>2, 'label'=>2),
                array('value'=>3, 'label'=>3)

        );
    }

}