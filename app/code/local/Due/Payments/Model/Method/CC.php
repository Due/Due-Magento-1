<?php

if (!class_exists('\Due\Due', false)) {
    require_once(Mage::getBaseDir('lib') . '/php-ecom-sdk/init.php');
}

class Due_Payments_Model_Method_CC extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Payment Method Code
     */
    const METHOD_CODE = 'due_cc';

    /**
     * Payment method code
     */
    public $_code = self::METHOD_CODE;

    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canOrder = true;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = false;
    protected $_canFetchTransactionInfo = false;
    protected $_canSaveCc = false;

    /**
     * Payment method blocks
     */
    protected $_infoBlockType = 'due/info_CC';
    protected $_formBlockType = 'due/form_CC';

    /**
     * Get initialized flag status
     * @return true
     */
    public function isInitializeNeeded()
    {
        return true;
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        parent::assignData($data);

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $mode = $data->getCcMode();
        $info = $this->getInfoInstance()
            ->setAdditionalInformation('mode', $mode);

        switch ($mode) {
            case 'saved_card':
                if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                    return $info;
                }

                $card_id = $data->getCcSaved();
                $saved = Mage::getModel('due/duecard')->load($card_id);
                if ($saved->getCustomerId() != Mage::getSingleton('customer/session')->getCustomer()->getId()) {
                    return $info;
                }

                $card = explode(':', $saved->getToken());
                $info->setAdditionalInformation('card_id', $card[0])
                    ->setAdditionalInformation('card_hash', $card[1])
                    ->setAdditionalInformation('risk_token', $card[2])
                    ->setAdditionalInformation('save_card', false)
                    ->setCcType($saved->getType())
                    ->setCcLast4($saved->getLast4())
                    ->setCcExpMonth($saved->getExpMonth())
                    ->setCcExpYear($saved->getExpYear());
                break;
            case 'new_card':
                $card = explode(':', $data->getCcDueToken());

                // For US Int Rail Type
	            if ($this->getConfigData('rail_type') === 'us_int') {
		            // Init Due
		            \Due\Due::setRailType($this->getConfigData('rail_type'));
		            if ($this->getConfigData('sandbox_mode') == '1') {
			            \Due\Due::setEnvName('stage');
			            \Due\Due::setApiKey($this->getConfigData('api_key_sandbox'));
			            \Due\Due::setAppId($this->getConfigData('app_id_sandbox'));
		            } else {
			            \Due\Due::setEnvName('prod');
			            \Due\Due::setApiKey($this->getConfigData('api_key'));
			            \Due\Due::setAppId($this->getConfigData('app_id'));
		            }

		            $customer = Mage::getSingleton('customer/session');

                    try {
                        $token_data = \Due\Tokenize::card(array(
                            'token' => $card[0],
                            'email' => $customer->isLoggedIn() ? $customer->getCustomer()->getEmail() : ''
                        ));
                    } catch(Exception $e) {
                        return $info;
                    }

		            if (!empty($token_data->customer_id)) {
			            $card[0] = $token_data->customer_id;
		            }
	            }

                $info->setAdditionalInformation('card_id', $card[0])
                    ->setAdditionalInformation('card_hash', $card[1])
                    ->setAdditionalInformation('risk_token', $card[2])
                    ->setAdditionalInformation('save_card', (bool)$data->getCcSaveCard())
                    ->setCcType($card[3])
                    ->setCcLast4($card[4])
                    ->setCcExpMonth($data->getCcExpMonth())
                    ->setCcExpYear($data->getCcExpYear());

                if ($data->getCcSaveCard()) {
                    $this->getCheckout()->setCcDueToken($data->getCcDueToken());
                }

                break;
        }

        return $info;
    }

	/**
	 * Check whether payment method can be used
	 * @param Mage_Sales_Model_Quote
	 * @return bool
	 */
	public function isAvailable($quote = null)
	{
		if (parent::isAvailable($quote) === false) {
			return false;
		}

		if (!$quote) {
			return false;
		}

		if ($this->getConfigData('rail_type') === 'us' &&
		    $quote->getQuoteCurrencyCode() !== 'USD')
		{
			return false;
		}

		return true;
	}

    /**
     * Validate payment method information object
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function validate()
    {
        if (parent::validate() === false) {
            return $this;
        }

        $paymentInfo = $this->getInfoInstance();
        $card_id = $paymentInfo->getAdditionalInformation('card_id');
        $card_hash = $paymentInfo->getAdditionalInformation('card_hash');
        if (empty($card_id) || empty($card_hash)) {
            throw new Mage_Core_Exception('Unable to use this card');
        }

        return $this;
    }

    /**
     * Instantiate state and set it to state object
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @throws Mage_Core_Exception
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        /** @var Mage_Sales_Model_Quote_Payment $info */
        $info = $this->getInfoInstance();

        /** @var Mage_Sales_Model_Order $order */
        $order = $info->getOrder();

	    // Set risk data
	    $risk_data = array();
	    $risk_data['items'] = array();
	    foreach($order->getAllVisibleItems() as $item) {
		    $risk_data['items'][] = array(
			    'description' => $item->getName(),
			    'amount' => $item->getRowTotalInclTax(),
			    'quantity' => (int)$item->getQtyOrdered()
		    );
	    }

	    // Get Billing info
	    $billingAddress = $order->getBillingAddress()->getStreet();
	    $billingCountryCode = $order->getBillingAddress()->getCountry();
	    $billingCountry = Mage::getModel('directory/country')->load($billingCountryCode)->getName();
	    $customer_data = array(
		    'first_name' => $order->getBillingAddress()->getFirstname(),
		    'last_name' => $order->getBillingAddress()->getLastname(),
		    'street_1' => $billingAddress[0],
		    'street_2' => (isset($billingAddress[1])) ? $billingAddress[1] : '',
		    'city' => (string)$order->getBillingAddress()->getCity(),
		    'state' => (string)$order->getBillingAddress()->getRegion(),
		    'zip' => (string)$order->getBillingAddress()->getPostcode(),
		    'country' => $billingCountry,
		    'phone' => (string)$order->getBillingAddress()->getTelephone(),
		    'email' => (string)$order->getBillingAddress()->getEmail(),
	    );

	    // Get shipping info
	    $shipping_data = array();
	    if (!$order->getIsVirtual()) {
		    $deliveryAddress = $order->getShippingAddress()->getStreet();
		    $deliveryCountryCode = $order->getShippingAddress()->getCountry();
		    $deliveryCountry = Mage::getModel('directory/country')->load($deliveryCountryCode)->getName();
		    $shipping_data = array(
			    'first_name' => $order->getShippingAddress()->getFirstname(),
			    'last_name' => $order->getShippingAddress()->getLastname(),
			    'street_1' => $deliveryAddress[0],
			    'street_2' => (isset($deliveryAddress[1])) ? $deliveryAddress[1] : '',
			    'city' => (string)$order->getShippingAddress()->getCity(),
			    'state' => (string)$order->getShippingAddress()->getRegion(),
			    'zip' => (string)$order->getShippingAddress()->getPostcode(),
			    'country' => $deliveryCountry,
		    );
	    }

        // Init Due
	    \Due\Due::setRailType($this->getConfigData('rail_type'));
        if ($this->getConfigData('sandbox_mode') == '1') {
            \Due\Due::setEnvName('stage');
            \Due\Due::setApiKey($this->getConfigData('api_key_sandbox'));
            \Due\Due::setAppId($this->getConfigData('app_id_sandbox'));
        } else {
            \Due\Due::setEnvName('prod');
            \Due\Due::setApiKey($this->getConfigData('api_key'));
            \Due\Due::setAppId($this->getConfigData('app_id'));
        }

        // Do transaction
        try {
            $transaction = \Due\Charge::card(array(
                'amount' => $order->getGrandTotal(),
                'currency' => $order->getOrderCurrency()->getCurrencyCode(),
                'card_id' => $info->getAdditionalInformation('card_id'),
                'card_hash' => $info->getAdditionalInformation('card_hash'),
                'unique_id' => $order->getIncrementId(),
                'customer_ip' => Mage::helper('core/http')->getRemoteAddr(),
                'rtoken' => $info->getAdditionalInformation('risk_token'),
                'rdata' => $risk_data,
                'customer' => $customer_data,
                'shipping' => $shipping_data
            ));
        } catch (Exception $e) {
            throw new Mage_Core_Exception($e->getMessage());
        }

        if ($transaction && $transaction->id) {
            // Save transaction
            Mage::helper('due')->createTransaction($order->getPayment(),
                null,
                $transaction->id,
                Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT,
                1,
                (array)$transaction
            );

            $invoice = Mage::helper('due')->makeInvoice($order, false);
            $invoice->setTransactionId($transaction->id);
            $invoice->save();

            // Payment success
            $message = sprintf('Payment success. Transaction Id: %s', $transaction->id);

            // Change order status
            /** @var Mage_Sales_Model_Order_Status $status */
            $status = Mage::helper('due')->getAssignedStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
            $order->setData('state', $status->getState());
            $order->setStatus($status->getStatus());
            $order->save();
            $order->sendNewOrderEmail();

            $order->addStatusHistoryComment($message);

            // Set state object
            $stateObject->setState($status->getState());
            $stateObject->setStatus($status->getStatus());
            $stateObject->setIsNotified(false);

            if ($info->getAdditionalInformation('save_card')) {
                $card = Mage::getModel('due/duecard');
                $card->setCustomerId(Mage::getSingleton('customer/session')->getCustomer()->getId())
                    ->setToken($this->getCheckout()->getCcDueToken())
                    ->setType($info->getCcType())
                    ->setLast4($info->getCcLast4())
                    ->setExpMonth($info->getCcExpMonth())
                    ->setExpYear($info->getCcExpYear())
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->save();
            }
        } elseif ($transaction && $transaction->error_message) {
            // Payment failed
            $message = sprintf('Payment failed. Details: %s', $transaction->error_message);

            // Cancel order
            $order->cancel();
            $order->addStatusHistoryComment($message);
            $order->save();

            throw new Mage_Core_Exception($message);
        } else {
            $message = 'Failed to perform payment';

            // Cancel order
            $order->cancel();
            $order->addStatusHistoryComment($message);
            $order->save();
            throw new Mage_Core_Exception($message);
        }
    }

    /**
     * Get config action to process initialization
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $paymentAction = $this->getConfigData('payment_action');
        return empty($paymentAction) ? true : $paymentAction;
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Refund capture
     * @param Varien_Object $payment
     * @param $amount
     * @return $this
     */
    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);

        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for refund.'));
        }

        if (!$payment->getLastTransId()) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid transaction ID.'));
        }

        // Load transaction Data
        $transactionId = $payment->getLastTransId();
        $transaction = $payment->getTransaction($transactionId);
        if (!$transaction) {
            Mage::throwException(Mage::helper('due')->__('Can\'t load last transaction.'));
        }

	    // Init Due
	    \Due\Due::setRailType($this->getConfigData('rail_type'));
	    if ($this->getConfigData('sandbox_mode') == '1') {
		    \Due\Due::setEnvName('stage');
		    \Due\Due::setApiKey($this->getConfigData('api_key_sandbox'));
		    \Due\Due::setAppId($this->getConfigData('app_id_sandbox'));
	    } else {
		    \Due\Due::setEnvName('prod');
		    \Due\Due::setApiKey($this->getConfigData('api_key'));
		    \Due\Due::setAppId($this->getConfigData('app_id'));
	    }

        // Do refund
        try {
            $transaction = \Due\Refund::doCardRefund(array(
                'customer_ip' => Mage::helper('core/http')->getRemoteAddr(),
                'amount' => $amount,
                'transaction_id' => $transactionId,
                'meta' => array(
                    'order_number' => $payment->getOrder()->getIncrementId(),
                    'refund_reason' => 'Refund from Magento admin'
                )
            ));
        } catch (Exception $e) {
            throw new Mage_Core_Exception($e->getMessage());
        }

        if ($transaction && $transaction->status === 'refunded') {
            // Add Credit Transaction
            $payment->setAnetTransType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
            $payment->setAmount($amount);

            $payment->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($transaction->id)
                ->setIsTransactionClosed(1);

        } elseif ($transaction && $transaction->error_message) {
            // Payment failed
            $message = sprintf('Refund failed. Details: %s', $transaction->error_message);
            throw new Mage_Core_Exception($message);
        } else {
            // Payment failed
            $message = 'Refund failed';
            throw new Mage_Core_Exception($message);
        }

        return $this;
    }
}
