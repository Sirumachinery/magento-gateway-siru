<?php

/**
 * Controller for Siru success/cancel/failure pages and Siru notifications.
 */
class Siru_Mobile_IndexController extends Mage_Core_Controller_Front_Action
{

    /**
     * User is redirected here after successful payment.
     */
    public function successAction()
    {
        if(isset($_GET['siru_event']) == true) {

            $signature = Mage::helper('siru_mobile/api')->getSignature();

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
            } else {
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

    public function callbackAction()
    {
    }

}
