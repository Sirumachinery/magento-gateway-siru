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
        $logger->debug('Siru payment controller: create payment for order id ' . $order_id);

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($order_id);
        if (!$order->getId()) {
            $logger->error(sprintf('Order %s was not found for processing.', $order_id));
            Mage::throwException('No order for processing found');
        }

        $customer = $order->getBillingAddress();

        $data = Mage::getStoreConfig('payment/siru_mobile');

        $successUrl =  Mage::getUrl('sirumobile/index/success', array('_secure' => false));
        $failUrl =  Mage::getUrl('sirumobile/index/failure', array('_secure' => false));
        $cancelUrl =  Mage::getUrl('sirumobile/index/failure', array('_secure' => false));
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
                ->set('purchaseCountry', $purchaseCountry)
                ->set('basePrice', $basePrice)
                ->set('redirectAfterSuccess', $successUrl)
                ->set('redirectAfterFailure', $failUrl)
                ->set('redirectAfterCancel', $cancelUrl)
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

            return $this->_redirectUrl($transaction['redirect']);

        // @TODO Even if creating payment fails, this will still create an order with status processing ??????

        } catch (\Siru\Exception\InvalidResponseException $e) {
            Mage::logException($e);
            $logger->error('Unable to contact payment API. Check credentials.');
            Mage::throwException($e->getMessage());

        } catch (\Siru\Exception\ApiException $e) {
            Mage::logException($e);
            $logger->error('Failed to create transaction. ' . implode(" ", $e->getErrorStack()));
            Mage::throwException($e->getMessage());
        }

    }

}
