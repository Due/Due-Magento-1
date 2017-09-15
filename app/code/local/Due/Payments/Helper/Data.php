<?php

class Due_Payments_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get Assigned Status
     * @param $status
     * @return Mage_Sales_Model_Order_Status
     */
    public function getAssignedStatus($status) {
        $status = Mage::getModel('sales/order_status')
            ->getCollection()
            ->joinStates()
            ->addFieldToFilter('main_table.status', $status)
            ->getFirstItem();
        return $status;
    }

    /**
     * Create Invoice
     * @param Mage_Sales_Model_Order $order
     * @param bool $online
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function makeInvoice(&$order, $online = false)
    {
        // Prepare Invoice
        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        $invoice->addComment(Mage::helper('due')->__('Auto-generated from Due'), false, false);
        $invoice->setRequestedCaptureCase($online ? Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE : Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
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
            Mage::throwException($e->getMessage());
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
     * Create transaction
     * @note: Use for only first transaction
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param $parentTransactionId
     * @param $transactionId
     * @param $type
     * @param int $IsTransactionClosed
     * @param array $fields
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    public function createTransaction($payment, $parentTransactionId, $transactionId, $type, $IsTransactionClosed = 0, $fields = array())
    {
        $failsafe = true;
        $ShouldCloseParentTransaction = true;

        // set transaction parameters
        $transaction = Mage::getModel('sales/order_payment_transaction')
            ->setOrderPaymentObject($payment)
            ->setTxnType($type)
            ->setTxnId($transactionId)
            ->isFailsafe($failsafe);

        $transaction->setIsClosed($IsTransactionClosed);

        // Set transaction addition information
        if (count($fields) > 0) {
            $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $fields);
        }

        // link with sales entities
        $payment->setLastTransId($transactionId);
        $payment->setCreatedTransaction($transaction);
        $payment->getOrder()->addRelatedObject($transaction);

        // link with parent transaction
        if ($parentTransactionId) {
            $transaction->setParentTxnId($parentTransactionId);
            // Close parent transaction
            if ($ShouldCloseParentTransaction) {
                $parentTransaction = $payment->getTransaction($parentTransactionId);
                if ($parentTransaction) {
                    $parentTransaction->isFailsafe($failsafe)->close(false);
                    $payment->getOrder()->addRelatedObject($parentTransaction);
                }
            }
        }

        return $transaction;
    }

    public function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    public function getStoredCards()
    {
        if (!$this->isCustomerLoggedIn()) {
            return array();
        }

        $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
        $cards = Mage::getModel('due/duecard')->getCollection()
            ->addFieldToFilter('customer_id', $customer_id);
        $data = $cards->toArray();
        if ($data['totalRecords'] > 0) {
            return $data['items'];
        }

        return array();
    }
}