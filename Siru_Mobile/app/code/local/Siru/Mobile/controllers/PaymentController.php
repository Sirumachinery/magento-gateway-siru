<?php

/**
 * Siru payment controller
 */
class Siru_Mobile_PaymentController extends Mage_Core_Controller_Front_Action
{

    /**
     * User is redirected here after he clicks "Place order" in checkout page.
     * @see  Siru_Mobile_Model_Payment::getOrderPlaceRedirectUrl()
     */
    public function createAction()
    {

        $logger = Mage::helper('siru_mobile/logger');

        $order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        // Check that order exists
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($order_id);
        if (!$order->getId()) {
            $logger->error(sprintf('Order %s was not found for processing.', $order_id));
            Mage::getSingleton('core/session')->addError($this->__('Order was not found for processing. Please try again.'));
            return $this->_redirect("checkout/cart");
        }

        // Check that Siru Mobile was the selected payment method
        if ($order->getPayment()->getMethodInstance()->getCode() !== 'siru_mobile') {
            $logger->error(sprintf(
                'Attempt to create Siru Mobile payment when order %s payment method is %s.',
                $order->getIncrementId(),
                $order->getPayment()->getMethodInstance()->getCode()
            ));

            Mage::getSingleton('core/session')->addError($this->__('Order payment method has changed. Please try again.'));
            return $this->_redirect("checkout/cart");
        }

        $logger->debug('Create payment for order id ' . $order_id);

        $customer = $order->getBillingAddress();

        $data = Mage::getStoreConfig('payment/siru_mobile');

        $redirectUrl =  Mage::getUrl('sirumobile/index/response', array('_secure' => false));
        $notifyUrl = Mage::getUrl('sirumobile/index/callback', array('_secure' => false));
        $taxClass = (int)$data['tax_class'];
        $purchaseCountry = $data['purchase_country'];
        $serviceGroup = $data['service_group'];
        $instantPay = (int)$data['instant_payment'];

        $basePrice = Mage::helper('siru_mobile/data')->getGrandTotalExclTaxForOrder($order);
        if($basePrice == 0) {
            $logger->error(sprintf('Order %s calculated base price was zero.', $order->getIncrementId()));
            Mage::getSingleton('core/session')->addError($this->__('Order did not have any payments due.'));
            return $this->_redirect("checkout/cart");
        }

        try {

            $api = Mage::helper('siru_mobile/api')->getApi();

            $transaction = $api->getPaymentApi()
                ->set('variant', 'variant2')
                ->set('purchaseCountry', $purchaseCountry)
                ->set('basePrice', $basePrice)
                ->set('redirectAfterSuccess', $redirectUrl)
                ->set('redirectAfterFailure', $redirectUrl)
                ->set('redirectAfterCancel', $redirectUrl)
                ->set('notifyAfterSuccess', $notifyUrl)
                ->set('notifyAfterFailure', $notifyUrl)
                ->set('notifyAfterCancel', $notifyUrl)
                ->set('taxClass', $taxClass)
                ->set('serviceGroup', $serviceGroup)
                ->set('instantPay', $instantPay)
                ->set('purchaseReference', $order->getIncrementId())
                ->set('customerReference', $order->getCustomerId())
                ->set('customerFirstName', $customer->getFirstname())
                ->set('customerLastName', $customer->getLastname())
                ->set('customerEmail', $customer->getEmail())
                ->createPayment();

            $logger->info(sprintf('Created new pending payment for order %s. UUID %s.', $order->getIncrementId(), $transaction['uuid']));

            $order->setState(
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                'Redirect user to Siru payment page.'
            );
            $order->save();

            return $this->_redirectUrl($transaction['redirect']);

        } catch (\Siru\Exception\InvalidResponseException $e) {
            $logger->error(sprintf('Failed to create transaction for order %s. Unable to contact payment API. Check credentials.', $order->getIncrementId()));

            return $this->handleException($e, $order);

        } catch (\Siru\Exception\ApiException $e) {
            $logger->error(sprintf('Failed to create transaction for order %s. %s', $order->getIncrementId(), implode(" ", $e->getErrorStack())));

            return $this->handleException($e, $order);
        }

    }

    /**
     * Logs exception and cancels order.
     * @param  Exception              $e
     * @param  Mage_Sales_Model_Order $order
     */
    private function handleException(Exception $e, Mage_Sales_Model_Order $order)
    {
        Mage::logException($e);

        Mage::getSingleton('core/session')->addError($this->__('There was a problem with the payment gateway. Please try again.'));

        $order->cancel();
        $order->addStatusHistoryComment('Failed to create payment.', Mage_Sales_Model_Order::STATE_CANCELED);
        $order->save();

        $quoteId = $order->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $quote->setIsActive(true)->save();

        return $this->_redirect("checkout/cart");
    }

}
