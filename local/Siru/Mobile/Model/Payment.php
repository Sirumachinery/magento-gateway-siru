<?php

/**
 * Class Siru_Mobile_Model_Payment]
 */
class Siru_Mobile_Model_Payment extends Mage_Payment_Model_Method_Abstract
{

    const CACHE_KEY_IP = 'siru_ip';
    const CACHE_TTL_IP = 86400;

    /**
     * Siru_Mobile_Model_Payment constructor.
     */
    public function __construct()
    {
        $this->verifyAvailability();

        if(Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            if ($lastQuoteId = Mage::getSingleton('checkout/session')->getLastQuoteId()){
                $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
                $quote->setIsActive(true)->save();
            }
        }
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

        $logger = Mage::helper('siru_mobile/logger');

//        $paymentInfo = $this->getInfoInstance();

        $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
        $logger->log($quoteId);

        $quote = Mage::getModel("sales/quote")->load($quoteId);

        $customer = $quote->_data;
        $logger->log($quote->_data);

        $data = Mage::getStoreConfig('payment/siru_mobile');

        $successUrl =  Mage::getUrl('sirumobile/index/success', array('_secure' => false));
        $failUrl =  Mage::getUrl('sirumobile/index/failure', array('_secure' => false));
        $cancelUrl =  Mage::getUrl('sirumobile/index/failure', array('_secure' => false));

        // Siru variant2 requires price w/o VAT
        // To avoid decimal errors, deduct VAT from total instead of using $order->get_subtotal()
        $total = substr($quote->_data['base_grand_total'], 0, 5);
        $taxClass = (int)$data['tax_class'];
        $taxPercentages = array(1 => 0.1, 2 => 0.14, 3 => 0.24);
        if (isset($taxPercentages[$taxClass]) == true) {
            $total = bcdiv($total, $taxPercentages[$taxClass] + 1, 2);
        }

        $purchaseCountry = $data['purchase_country'];
        $serviceGroup = $data['service_group'];
        $instantPay = $data['instant_payment'];

        try {

            $api = Mage::helper('siru_mobile/api')->getApi();

            $transaction = $api->getPaymentApi()
                ->set('variant', 'variant2')
                ->set('purchaseCountry', $purchaseCountry)
                ->set('basePrice', $total)
                ->set('redirectAfterSuccess', $successUrl)
                ->set('redirectAfterFailure', $failUrl)
                ->set('redirectAfterCancel', $cancelUrl)
                ->set('taxClass', $taxClass)
                ->set('serviceGroup', $serviceGroup)
                ->set('instantPay', $instantPay)
#                ->set('purchaseReference', $order->getId())
                ->set('customerReference', $customer['customer_id'])
                ->set('customerFirstName', $customer['customer_firstname'])
                ->set('customerLastName', $customer['customer_lastname'])
                ->set('customerEmail', $customer['customer_email'])
                ->createPayment();

#            $logger->log(sprintf('Created new pending payment for order %s. UUID %s.', $order->getId(), $transaction['uuid']), Zend_Log::INFO);

            return $transaction['redirect'];

        // @TODO Even if creating payment fails, this will still create an order with status processing ??????

        } catch (\Siru\Exception\InvalidResponseException $e) {
            Mage::logException($e);
            $logger->log('Unable to contact payment API. Check credentials.', Zend_Log::ERR);
            Mage::throwException($e->getMessage());

        } catch (\Siru\Exception\ApiException $e) {
            Mage::logException($e);
            $logger->log('Failed to create transaction. ' . implode(" ", $e->getErrorStack()), Zend_Log::ERR);
            Mage::throwException($e->getMessage());
        }

        return;
    }

    /**
     * Run various checks to see if Siru mobile payments are available for this purchase.
     */
    private function verifyAvailability()
    {

        $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
        $quote = Mage::getModel("sales/quote")->load($quoteId);

        if ($this->_canUseCheckout == true) {

            // Make sure payment gateway is configured
            $data = Mage::getStoreConfig('payment/siru_mobile');
            $merchantId = $data['merchant_id'];
            $secret = $data['merchant_secret'];

            if (empty($merchantId) == true || empty($secret) == true) {
                $this->_canUseCheckout = false;
                return;
            }

            // Make sure cart total does not exceed set maximum payment amount.
            $limit = number_format($data['maximum_payment'], 2);
            $total = substr($quote->_data['base_grand_total'], 0, 5);

            if (bccomp($limit, 0, 2) == 1 && bccomp($limit, $total, 2) == -1) {
                $this->_canUseCheckout = false;
                return;
            }

            // Make sure user is using mobile internet connection.
            $this->verifyMobileInternetConnection();

        }

    }

    /**
     * Checks if users IP-address is allowed to make mobile payments. If not, remove Siru from payment options.
     */
    private function verifyMobileInternetConnection()
    {
        if ($this->_canUseCheckout == true) {
            $ip = Mage::helper('core/http')->getRemoteAddr();

            $mageCache = Mage::app()->getCache();
            $cache = $mageCache->load(self::CACHE_KEY_IP);

            if($cache != false) {
                $cache = (array) unserialize($cache);
            } else {
                $cache = array();
            }

            if(isset($cache[$ip])) {
                if($cache[$ip] == false) {
                    $this->_canUseCheckout = false;
                }

                return;
            }

            $api = Mage::helper('siru_mobile/api')->getApi();

            try {

                $allowed = $api->getFeaturePhoneApi()->isFeaturePhoneIP($ip);

                // Cache result
                $cache[$ip] = $allowed;
                $mageCache->save(serialize($cache), self::CACHE_KEY_IP, array('siru_cache'), self::CACHE_TTL_IP);

                if ($allowed == false) {
                    $logger->log(sprintf('Hide Siru Mobile payment option for IP %s.', $ip), Zend_Log::DEBUG);
                    $this->_canUseCheckout = false;
                }

            } catch (\Siru\Exception\ApiException $e) {
                Mage::logException($e);
                $logger->log(sprintf('Unable to verify if %s is allowed to use mobile payments. %s', $ip, $e->getMessage()), Zend_Log::ERR);
            }

        }

        return;
    }

}
