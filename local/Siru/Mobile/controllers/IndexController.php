<?php

/**
 * Controller for Siru success/cancel/failure pages and Siru notifications.
 */
class Siru_Mobile_IndexController extends Mage_Core_Controller_Front_Action
{

    /**
     * User is redirected here after successful payment.
     * @todo check if order status allows status to be changed
     * @todo compare purchase reference to order so that we complete correct order
     */
    public function responseAction()
    {
        $event = $this->getAuthenticatedSiruEvent($_GET);
        if($event == false) {
            $this->_redirect("checkout/onepage");
        }

        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        switch($event) {
                case 'success':
                    $this->completePayment($order);
                    return $this->_redirect('checkout/onepage/success');

                case 'cancel':
                    $this->cancelOrder($order, 'User canceled payment.');
                    return $this->_redirect('checkout/onepage');

                case 'failure':
                    $this->cancelOrder($order, 'Payment failed.');
                    return $this->_redirect('checkout/onepage');
        }

        return $this->_redirect("checkout/onepage");
    }

    /**
     * Handles notifications from Siru mobile.
     */
    public function callbackAction()
    {
        $entityBody = file_get_contents('php://input');
        $entityBodyAsJson = json_decode($entityBody, true);

        if(is_array($entityBodyAsJson) && isset($entityBodyAsJson['siru_event'])) {

            $event = $this->getAuthenticatedSiruEvent($entityBodyAsJson);

            if($event == false) {
                Mage::helper('siru_mobile/logger')->warning('Call to Siru payment callback handler with invalid event or signature.');
                $this->getResponse()->setHeader('HTTP/1.0','403',true);
                return;
            }

            $orderId = $entityBodyAsJson['siru_purchaseReference'];
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

            switch($event) {
                case 'success':
                    $this->completePayment($order);
                    break;

                case 'cancel':
                    $this->cancelOrder($order, 'User canceled payment.');
                    break;

                case 'failure':
                    $this->cancelOrder($order, 'Payment failed.');
                    break;
            }

            echo 'OK';
        } else {
            $this->getResponse()->setHeader('HTTP/1.0','500',true);
        }
    }

    /**
     * Authenticates given parameters and returns siru_event value.
     * @param  array  $data Data from GET or from JSON
     * @return string|boolean
     */
    private function getAuthenticatedSiruEvent(array $data)
    {
        if(isset($data['siru_event']) == true) {

            $signature = Mage::helper('siru_mobile/api')->getSignature();

            if($signature->isNotificationAuthentic($data)) {
                return $data['siru_event'];
            }

        }

        return false;
    }

    private function completePayment(Mage_Sales_Model_Order $order)
    {
        $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE);

        $order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
        $order->save();

        // Empty Cart
        Mage::getSingleton('checkout/cart')->truncate();
        Mage::getSingleton('checkout/cart')->save();
    }

    /**
     * Cancels order and stores optional status message about event.
     * @param  Mage_Sales_Model_Order $order
     * @param  string|null            $message
     */
    private function cancelOrder(Mage_Sales_Model_Order $order, $message = null)
    {
        $order->cancel();
        if($message) {
            $order->addStatusHistoryComment($message, Mage_Sales_Model_Order::STATE_CANCELED);
        }
        $order->save();
    }

}
