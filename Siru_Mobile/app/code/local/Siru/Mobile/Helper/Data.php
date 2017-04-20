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
     * Returns order grand total excluding VAT. Total is rounded to 2 decimal points.
     * 
     * @param  Mage_Sales_Model_Order $order
     * @return string
     */
    public function getGrandTotalExclTaxForOrder(Mage_Sales_Model_Order $order)
    {
        $grandTotal = bcsub($order->getGrandTotal(), $order->getTaxAmount(), 3);
        return number_format($grandTotal, 2, '.', '');
    }

    /**
     * Deducts VAT percent based on Siru tax class from value.
     * 
     * @param  float  $amount
     * @param  int    $taxClass Siru tax class
     * @return string
     */
    public function deductTaxClass($amount, $taxClass)
    {
        $totalDue = number_format($amount, 2, '.', '');

        if (isset($this->taxPercentages[$taxClass]) == true) {
            $totalDue = bcdiv($totalDue, $this->taxPercentages[$taxClass] + 1, 3);
            $totalDue = number_format($totalDue, 2, '.', '');
        }

        return $totalDue;
    }

    public function getGrandTotalExclTaxForQuote(Mage_Sales_Model_Quote $quote)
    {
        $total = 0;
        $address = $quote->getShippingAddress();
        if ($address) {
            $total = $address->getGrandTotal() - $address->getTaxAmount();
        }
     
        return max($total, 0);
    }

}
