<?php

/**
 * Class Siru_Sirumobilepaymentmethod_Helper_Data
 */
class Siru_Mobile_Helper_Data extends Mage_Core_Helper_Abstract {

    private $taxPercentages = array(
        1 => 0.1,
        2 => 0.14,
        3 => 0.24
    );

    /**
     * Deducts VAT from total amount due based on configured Siru tax class.
     *
     * Siru variant2 payments require price w/o VAT.
     * 
     * @param  float $totalDue Amount due including VAT
     * @param  int   $taxClass Siru tax class
     * @return float
     */
    public function calculateBasePrice($totalDue, $taxClass)
    {
        if (isset($this->taxPercentages[$taxClass]) == true) {
            $total = bcdiv($total, $this->taxPercentages[$taxClass] + 1, 2);
        }

        return $total;
    }

}
