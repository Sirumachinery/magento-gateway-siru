<?php

/**
 * Class Siru_Mobile_IndexController
 */
class Siru_Mobile_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * method successAction
     */
    public function successAction()
    {
        $code_path = Mage::getBaseDir('code');

        $path = $code_path . '/local/Siru/Mobile/vendor/autoload.php';

        require_once($path);

        $data = Mage::getStoreConfig('payment/siru_mobile');

        $merchantId = $data['merchant_id'];
        $secret = $data['merchant_secret'];

        $signature = new \Siru\Signature($merchantId, $secret);


        if(isset($_GET['siru_event']) == true) {

            if($signature->isNotificationAuthentic($_GET)) {

                if($_GET['siru_event'] == 'success') {

                    $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

                    $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

                    $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE);

                    $order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
                    $order->save();

                    // Empty Cart
                    Mage::getSingleton('checkout/cart')->truncate();
                    Mage::getSingleton('checkout/cart')->save();

                    return $this->_redirect('checkout/onepage/success');
                }
            }else{
                return $this->_redirect('checkout/onepage');
            }
        }

        return $this->_redirect("/");

    }

    /**
     * method failureAction
     * @return Mage_Core_Controller_Varien_Action
     */
    public function failureAction()
    {
        return $this->_redirect('checkout/onepage');
    }

    /**
     * method cancelAction
     * @return Mage_Core_Controller_Varien_Action
     */
    public function cancelAction()
    {
        return $this->_redirect('checkout/onepage');
    }





}