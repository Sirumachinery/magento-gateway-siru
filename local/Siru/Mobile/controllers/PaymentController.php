<?php

/**
 * Siru payment controller
 */
class Siru_Mobile_PaymentController extends Mage_Core_Controller_Front_Action
{

    /**
     * User is redirected here after he clicks "Place order" in checkout page.
     * @see  Siru_Mobile_Model_Payment::getOrderPlaceRedirectUrl()
     * @todo store Siru UUID to order
     * @todo Show more graceful error message if API call fails, order is not found or order value due is zero
     */
    public function createAction()
    {

        $logger = Mage::helper('siru_mobile/logger');

        $order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $logger->debug('Siru payment controller: create payment for order id ' . $order_id);

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($order_id);
        if (!$order->getId()) {
            $logger->error(sprintf('Order %s was not found for processing.', $order_id));
            Mage::throwException('No order for processing found');
        }

        $customer = $order->getBillingAddress();

        $data = Mage::getStoreConfig('payment/siru_mobile');

        $redirectUrl =  Mage::getUrl('sirumobile/index/response', array('_secure' => false));
        $notifyUrl = Mage::getUrl('sirumobile/index/callback', array('_secure' => false));
        $taxClass = (int)$data['tax_class'];
        $purchaseCountry = $data['purchase_country'];
        $serviceGroup = $data['service_group'];
        $instantPay = $data['instant_payment'];

        $basePrice = Mage::helper('siru_mobile/data')->calculateBasePrice(
            $order->getTotalDue(),
            $taxClass
        );

        try {

            $api = Mage::helper('siru_mobile/api')->getApi();

            $transaction = $api->getPaymentApi()
                ->set('variant', 'variant2')
#                ->set('merchantId', '22222')
                ->set('purchaseCountry', $purchaseCountry)
                ->set('basePrice', $basePrice)
                ->set('redirectAfterSuccess', $redirectUrl)
                ->set('redirectAfterFailure', $redirectUrl)
                ->set('redirectAfterCancel', $redirectUrl)
#                ->set('notifyAfterSuccess', $notifyUrl)
#                ->set('notifyAfterFailure', $notifyUrl)
#                ->set('notifyAfterCancel', $notifyUrl)
                ->set('taxClass', $taxClass)
                ->set('serviceGroup', $serviceGroup)
                ->set('instantPay', $instantPay)
                ->set('purchaseReference', $order->getId())
                ->set('customerReference', $order->getCustomerId())
                ->set('customerFirstName', $customer->getFirstname())
                ->set('customerLastName', $customer->getLastname())
                ->set('customerEmail', $customer->getEmail())
                ->createPayment();

            $logger->info(sprintf('Created new pending payment for order %s. UUID %s.', $order->getId(), $transaction['uuid']));

            $order->setState(
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                'Redirect user to Siru payment page.'
            );
            $order->save();

            return $this->_redirectUrl($transaction['redirect']);

        } catch (\Siru\Exception\InvalidResponseException $e) {
            $logger->error('Unable to contact payment API. Check credentials.');

            $this->handleException($e, $order);

        } catch (\Siru\Exception\ApiException $e) {
            $logger->error('Failed to create transaction. ' . implode(" ", $e->getErrorStack()));

            $this->handleException($e, $order);
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

        $order->cancel();
        $order->addStatusHistoryComment('Failed to create payment.', Mage_Sales_Model_Order::STATE_CANCELED);
        $order->save();
        throw $e;
    }

}
