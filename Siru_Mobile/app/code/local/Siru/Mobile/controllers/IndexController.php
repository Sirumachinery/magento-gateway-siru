<?php

/**
 * Controller for Siru success/cancel/failure pages and Siru notifications.
 */
class Siru_Mobile_IndexController extends Mage_Core_Controller_Front_Action
{

    private $mode = 'ReturnUrl';

    /**
     * User is redirected here after successful payment.
     * @todo wrap in transaction
     */
    public function responseAction()
    {
        $query = Mage::app()->getRequest()->getQuery();

        $event = $this->getAuthenticatedSiruEvent($query);
        if($event == false) {
            $this->_redirect("checkout/onepage");
        }

        // Use order id from purchase reference instead of session.
        // Otherwise user could create new order with more products.
        $order = $this->getOrderFromParams($query);
        if($order == false) {
            return $this->_redirect("/");
        }

        $this->logEvent($event, $order);

        switch($event) {
                case 'success':
                    $this->completePayment($order, $query['siru_uuid']);
                    return $this->_redirect('checkout/onepage/success');

                case 'cancel':
                    $this->cancelOrder($order, 'User canceled payment.');
                    Mage::getSingleton('core/session')->addNotice($this->__('Payment was canceled.'));
                    return $this->_redirect("checkout/cart");

                case 'failure':
                    $this->cancelOrder($order, 'Payment failed.');
#                    Mage::getSingleton('core/session')->addError($this->__('Payment was unsuccessful.'));
                    return $this->_redirect("checkout/onepage/failure");
        }

    }

    /**
     * Handles notifications from Siru mobile.
     * @todo wrap in transaction
     */
    public function callbackAction()
    {
        $this->mode = 'Notification';

        $entityBody = Mage::app()->getRequest()->getRawBody();
        $entityBodyAsJson = json_decode($entityBody, true);

        if(is_array($entityBodyAsJson) && isset($entityBodyAsJson['siru_event'])) {

            $event = $this->getAuthenticatedSiruEvent($entityBodyAsJson);

            if($event == false) {
                $this->getResponse()->setHeader('HTTP/1.0','403', true);
                return;
            }

            $order = $this->getOrderFromParams($entityBodyAsJson);
            if($order == false) {
                echo 'Order not found';
                return;
            }

            $this->logEvent($event, $order);

            switch($event) {
                case 'success':
                    $this->completePayment($order, $entityBodyAsJson['siru_uuid']);
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
            $this->getResponse()->setHeader('HTTP/1.0','500', true);
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

            Mage::helper('siru_mobile/logger')->warning(sprintf('%s: Invalid or missing signature for event %s.', $this->mode, $data['siru_event']));

        }

        return false;
    }

    /**
     * Takes parameters received from Siru and returns correct order.
     * 
     * @param  array  $data
     * @return Mage_Sales_Model_Order
     */
    private function getOrderFromParams(array $data)
    {
        $incrementId = $data['siru_purchaseReference'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);

        // Make sure order exists
        if($order->getId() == false) {
            $logger->error(sprintf(
                '%s: Order increment_id %s was not found (%s event).',
                $this->mode,
                $incrementId,
                $data['siru_event']
            ));
            return false;
        }

        // Make sure order payment method was Siru
        $paymentMethod = $order->getPayment()->getMethodInstance();
        if (!($paymentMethod instanceof Siru_Mobile_Model_Payment)) {
            $logger->error(sprintf(
                '%s: Order increment_id %s was found (%s event) but with invalid payment method "%s".',
                $this->mode,
                $incrementId,
                $data['siru_event'],
                get_class($paymentMethod)
            ));
            return false;
        }

        return $order;
    }

    /**
     * Just creates log message about siru_event.
     * @param  string                 $event siru_event value
     * @param  Mage_Sales_Model_Order $order
     */
    private function logEvent($event, Mage_Sales_Model_Order $order)
    {
        Mage::helper('siru_mobile/logger')->info(sprintf(
            '%s from Siru with event %s for order increment_id %s. Current order status is %s.',
            $this->mode,
            $event,
            $order->getIncrementId(),
            $order->getStatus()
        ));
    }

    /**
     * Cancels order and stores optional status message about event.
     * @param  Mage_Sales_Model_Order $order
     * @param  string|null            $message
     */
    private function cancelOrder(Mage_Sales_Model_Order $order, $message = null)
    {
        if($order->getState() === Mage_Sales_Model_Order::STATE_CANCELED) {
            // Already canceled, ignore
            return;
        }

        if($order->canCancel() == false) {
            Mage::helper('siru_mobile/logger')->error(sprintf(
                'Order increment_id %s status %s does not allow cancelation.',
                $order->getIncrementId(),
                $order->getStatusLabel()
            ));
            return;
        }

        $order->cancel();
        if($message) {
            $order->addStatusHistoryComment($message, Mage_Sales_Model_Order::STATE_CANCELED);
        }
        $order->save();

        // Re-activate quote from which order was created
        $quoteId = $order->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $quote->setIsActive(true)->save();
    }

    /**
     * Completes the payment and order.
     * 
     * @param  Mage_Sales_Model_Order $order
     * @param  string                 $uuid  Siru UUID
     * @todo   Ignore if order is already completed?
     */
    private function completePayment(Mage_Sales_Model_Order $order, $uuid)
    {

        // As a precaution, update canceled order to processing
        if ($order->getState() === Mage_Sales_Model_Order::STATE_CANCELED) {
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
            $order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
            $order->save();

            foreach ($order->getAllItems() as $item) {
                $item->setQtyCanceled(0);
                $item->save();
            }
        }

        // Create transaction from payment
        $this->createTransactionForOrder($order, $uuid);

        // Update order status if needed
        $new_status = $order->getPayment()->getMethodInstance()->getConfigData('order_status_capture');
        $status = $this->getStatusModel($new_status);

        if($order->getStatus() != $status->getStatus()) {
            $order->setData('state', $status->getState());
            $order->setStatus($status->getStatus());
            $order->addStatusHistoryComment(Mage::helper('siru_mobile')->__('Order has been paid'), $new_status);
        }

        // Create invoice
        if($order->canInvoice() == true) {
            $invoice = $this->createInvoiceForOrder($order);
        }

        // We're done! \o/
        $order->save();

        $order->sendNewOrderEmail();

        // Empty Cart
        Mage::getSingleton('checkout/cart')->truncate();
        Mage::getSingleton('checkout/cart')->save();
    }

    /**
     * Creates transaction for payment.
     * 
     * @param  Mage_Sales_Model_Order                          $order
     * @param  string                                          $uuid  UUID from Siru API
     * @return Mage_Sales_Model_Order_Payment_Transaction|null
     * @todo   Could we create transaction in PaymentController and store UUID there already?
     */
    private function createTransactionForOrder(Mage_Sales_Model_Order $order, $uuid)
    {
        // Lookup Transaction
        $collection = Mage::getModel('sales/order_payment_transaction')
            ->getCollection()
            ->addAttributeToFilter('txn_id', $uuid);

        if(count($collection) > 0) {
            Mage::helper('siru_mobile/logger')->info(sprintf('Transaction %s of order %s already processed.', $uuid, $order->getIncrementId()));
            return $collection->getFirstItem();
        }

        // Set Payment Transaction Id
        // @todo Does this do anything really?? There is last_trans_id field in payments table
        $payment = $order->getPayment();
        $payment->setTransactionId($uuid);

        $message = Mage::helper('siru_mobile')->__('Transaction Status: %s.', $transaction_status);
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, true, $message);
        $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, array('uuid' => $uuid));
        $transaction->isFailsafe(true)->close(false);
        $transaction->setMessage('Transaction completed');
        $transaction->save();

        try {
            $order->save();
            Mage::helper('siru_mobile/logger')->info(sprintf('Created transaction %s for order %s', $transaction->getId(), $order->getIncrementId()));
        } catch (Exception $e) {
            Mage::helper('siru_mobile/logger')->error(sprintf('Failed to create transaction for order %s. %s', $order->getIncrementId(), $e->getMessage()));
            Mage::logException($e);
            return null;
        }

        return $transaction;
    }

    /**
     * Create Invoice
     * @param  Mage_Sales_Model_Order         $order
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function createInvoiceForOrder(Mage_Sales_Model_Order $order)
    {

        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        $invoice->addComment(Mage::helper('siru_mobile')->__('Auto-generated from Siru Mobile module'), false, false);
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->register();

        $invoice->getOrder()->setIsInProcess(true);

        try {
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();
        } catch (Mage_Core_Exception $e) {
            // Save Error Message
            $order->addStatusToHistory(
                $order->getStatus(),
                'Failed to create invoice: ' . $e->getMessage(),
                true
            );
            Mage::helper('siru_mobile/logger')->error(sprintf('Failed to create invoice for order %s. %s', $order->getIncrementId(), $e->getMessage()));
            throw $e;
        }

        $invoice->setIsPaid(true);

        // Assign Last Transaction Id with Invoice
        $transactionId = $invoice->getOrder()->getPayment()->getLastTransId();
        if ($transactionId) {
            $invoice->setTransactionId($transactionId);
            $invoice->save();
        }

        return $invoice;
    }

    /**
     * Get status object based on status.
     * 
     * @param  string                         $status
     * @return Mage_Sales_Model_Order_Status
     */
    private function getStatusModel($status) 
    {
        $status = Mage::getModel('sales/order_status')
            ->getCollection()
            ->joinStates()
            ->addFieldToFilter('main_table.status', $status)
            ->getFirstItem();
        return $status;
    }

}
