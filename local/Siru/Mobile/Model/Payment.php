<?php

/**
 * Class Siru_Mobile_Model_Payment]
 */
class Siru_Mobile_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Siru_Mobile_Model_Payment constructor.
     */
    public function __construct()
    {
        $this->includes();
        $this->wc_siru_disable_on_order_total();
        $this->wc_siru_verify_ip();
    }

    /**
     * @var string
     */
    protected $_code = 'siru_mobile';

    protected $_formBlockType = 'siru_mobile/form_desc';

    /**
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
//        $paymentInfo = $this->getInfoInstance();

        $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();

        $quote = Mage::getModel("sales/quote")->load($quoteId);

        $order = Mage::getModel('sales/order')->load($quoteId);

//        $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true)->save();
//        echo '<pre>';

//        print_r($quote);
//        exit;


        $customer = $quote->_data;

        $data = Mage::getStoreConfig('payment/siru_mobile');

        $merchantId = $data['merchant_id'];
        $secret = $data['merchant_secret'];
        $purchaseCountry = $data['purchase_country'];

        $signature = new \Siru\Signature(75, '19732400c951900ef5239f78f3d29b007678a75f');

        $api = new \Siru\API($signature);

        // Select sandbox environment (default)
        $api->useStagingEndpoint();

        // You can set default values for all payment requests (not required)
        $api->setDefaults([
            'variant' => 'variant2',
            'purchaseCountry' => 'FI'
        ]);


        $url = Mage::getUrl('checkout/onepage', array('_secure' => true));
        $successUrl = Mage::getUrl('checkout/onepage/success', array('_secure' => true));

        try {

            $total = $quote->_data['base_grand_total'];
            $total = number_format($total, 2);

            $taxClass = (int)$data['tax_class'];

            // Siru variant2 requires price w/o VAT
            // To avoid decimal errors, deduct VAT from total instead of using $order->get_subtotal()
            $taxPercentages = array(1 => 0.1, 2 => 0.14, 3 => 0.24);
            if (isset($taxPercentages[$taxClass]) == true) {
                $total = bcdiv($total, $taxPercentages[$taxClass] + 1, 2);
            }

            $serviceGroup = $data['service_group'];
            $instantPay = $data['instant_payment'];

            $transaction = $api->getPaymentApi()
                ->set('basePrice', '10.00')
                ->set('redirectAfterSuccess', $successUrl)
                ->set('redirectAfterFailure', $url)
                ->set('redirectAfterCancel', $url)
                ->set('taxClass', '3')
                ->set('serviceGroup', '2')
                ->set('instantPay', '1')
//              ->set('customerNumber', '442079460916')
                ->set('title', 'Concert ticket')
                ->set('customerLocale', 'en_GB')
                ->set('description', 'Magento ')
                ->createPayment();

//            return Mage::getUrl('siru_mobile/payment/redirect', array('_secure' => false));
            return $transaction['redirect'];

        } catch (\Siru\Exception\InvalidResponseException $e) {
            error_log('Siru Payment Gateway: Unable to contact payment API. Check credentials.');

        } catch (\Siru\Exception\ApiException $e) {
            error_log('Siru Payment Gateway: Failed to create transaction. ' . implode(" ", $e->getErrorStack()));
        }

        return;
    }

    /**
     *
     */
    public function includes()
    {

        $code_path = Mage::getBaseDir('code');


        $path = $code_path . '/local/Siru/Mobile/vendor/autoload.php';

        return require_once($path);
    }

    /**
     * Removes siru payment gateway option if maximum payment allowed is set and cart total exceeds it.
     */
    private function wc_siru_disable_on_order_total()
    {

        $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
        $quote = Mage::getModel("sales/quote")->load($quoteId);

        if ($this->_canUseCheckout == true) {

            $data = Mage::getStoreConfig('payment/siru_mobile');
            $merchantId = $data['merchant_id'];

            if (empty($merchantId) == true) {

                $this->_canUseCheckout = false;

            } else {

                $limit = number_format($data['maximum_payment'], 2);
                $total = number_format($quote->_data['base_grand_total'], 2);

                if (bccomp($limit, 0, 2) == 1 && bccomp($limit, $total, 2) == -1) {

                    $this->_canUseCheckout = false;
                }

            }

        }

        return;
    }

    /**
     * Checks if users IP-address is allowed to make mobile payments. If not, remove Siru from payment options.
     */
    private function wc_siru_verify_ip()
    {
        if ($this->_canUseCheckout == true) {
            $ip = Mage::helper('core/http')->getRemoteAddr();

//            $cache = (array) get_transient('wc_siru_ip_check');
            $data = Mage::getStoreConfig('payment/siru_mobile');

            $merchantId = $data['merchant_id'];
            $secret = $data['merchant_secret'];

            $signature = new \Siru\Signature($merchantId, $secret);

            $api = new \Siru\API($signature);

            // Use production endpoint if configured by admin
            $endPoint = $data['live_environment'];


            if (!$endPoint) {
                $api->useStagingEndpoint();
            }

            try {

                $allowed = $api->getFeaturePhoneApi()->isFeaturePhoneIP($ip);

                // Cache result for one houre
//            $cache[$ip] = $allowed;
//            set_transient('wc_siru_ip_check', $cache, 3600);

                if ($allowed == false) {
                    $this->_canUseCheckout = false;
                }

            } catch (\Siru\Exception\ApiException $e) {
                error_log(sprintf('Siru Payment Gateway: Unable to verify if %s is allowed to use mobile payments. %s', $ip, $e->getMessage()));
            }

        }

        return;
    }


}