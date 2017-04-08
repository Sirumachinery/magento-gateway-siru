<?php

/**
 * Class Siru_Mobile_Model_Payment
 */
class Siru_Mobile_Model_Payment extends Mage_Payment_Model_Method_Abstract
{

    const CACHE_KEY_IP = 'siru_ip';
    const CACHE_TTL_IP = 86400;

    /**
     * @var string
     */
    protected $_code = 'siru_mobile';

    /**
     * @var string
     */
    protected $_formBlockType = 'siru_mobile/form_desc';

    /**
     * @var bool
     */
    protected $_canUseCheckout = true;

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
     * Returns URL where user is redirected after he clicks Place order in checkout.
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('sirumobile/payment/create', array('_secure' => false));
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
                    $logger->debug(sprintf('Hide Siru Mobile payment option for IP %s.', $ip));
                    $this->_canUseCheckout = false;
                }

            } catch (\Siru\Exception\ApiException $e) {
                Mage::logException($e);
                $logger->error(sprintf('Unable to verify if %s is allowed to use mobile payments. %s', $ip, $e->getMessage()));
            }

        }

        return;
    }

}
