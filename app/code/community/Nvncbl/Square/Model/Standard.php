<?php

//require_once 'Nvncbl/Square/lib/Square.php';
require_once 'Nvncbl/Square/autoload.php';

class Nvncbl_Square_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'nvncbl_square';

	protected $_isInitializeNeeded      = false;
	protected $_canUseForMultishipping  = true;
	protected $_isGateway               = true;
	protected $_canAuthorize            = true;
	protected $_canCapture              = true;
	protected $_canCapturePartial       = true;
	protected $_canRefund               = true;
	protected $_canRefundInvoicePartial = true;
	protected $_canVoid                 = true;
	protected $_canCancelInvoice        = true;
	protected $_canUseInternal          = true;
	protected $_canUseCheckout          = true;
	protected $_canSaveCc               = false;
	protected $_formBlockType           = 'nvncbl_square/form_standard';

	// Docs: http://docs.magentocommerce.com/Mage_Payment/Mage_Payment_Model_Method_Abstract.html
	// mixed $_canCreateBillingAgreement
	// mixed $_canFetchTransactionInfo
	// mixed $_canManageRecurringProfiles
	// mixed $_canOrder
	// mixed $_canReviewPayment
	// array $_debugReplacePrivateDataKeys
	// mixed $_infoBlockType

	/**
	 * Square Modes
	 */
	const TEST = 'test';
	const LIVE = 'live';

	public function __construct()
	{
		$this->store = $store = $this->getStore();
		$mode = $store->getConfig('payment/nvncbl_square/square_mode');
		$path = "payment/nvncbl_square/square_{$mode}_sk";
		$apiKey = $store->getConfig($path);
	}

	protected function getStore()
	{
		// Admins may be viewing an order placed on a specific store
		if (Mage::app()->getStore()->isAdmin())
		{
			try
			{
				if (Mage::app()->getRequest()->getParam('order_id'))
				{
					$orderId = Mage::app()->getRequest()->getParam('order_id');
					$order = Mage::getModel('sales/order')->load($orderId);
					$store = $order->getStore();
				}
				elseif (Mage::app()->getRequest()->getParam('invoice_id'))
				{
					$invoiceId = Mage::app()->getRequest()->getParam('invoice_id');
					$invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
					$store = $invoice->getStore();
				}
				elseif (Mage::app()->getRequest()->getParam('creditmemo_id'))
				{
					$creditmemoId = Mage::app()->getRequest()->getParam('creditmemo_id');
					$creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemoId);
					$store = $creditmemo->getStore();
				}
				else
				{
					// We are creating a new order
					$store = $this->getSessionQuote()->getStore();
				}

				if (!empty($store) && $store->getId())
					return $store;
			}
			catch (Exception $e) {}
		}

		// Users get the store they are on
		return Mage::app()->getStore();
	}

	protected function getCustomerEmail()
	{
		if ($this->customerEmail)
			return $this->customerEmail;

		$quote = $this->getSessionQuote();

		if ($quote)
			$email = trim(strtolower($quote->getCustomerEmail()));

		// This happens with guest checkouts
		if (empty($email))
			$email = trim(strtolower($quote->getBillingAddress()->getEmail()));

		return $this->customerEmail = $email;
	}

	protected function getCustomerId()
	{
		// If we are in the back office
		if (Mage::app()->getStore()->isAdmin())
		{
			return Mage::getSingleton('adminhtml/sales_order_create')->getQuote()->getCustomerId();
		}
		// If we are on the checkout page
		else if (Mage::getSingleton('customer/session')->isLoggedIn())
		{
			return Mage::getSingleton('customer/session')->getCustomer()->getId();
		}

		return null;
	}

	protected function getSessionQuote()
	{
		// If we are in the back office
		if (Mage::app()->getStore()->isAdmin())
		{
			return Mage::getSingleton('adminhtml/sales_order_create')->getQuote();
		}
		// If we are a user
		return Mage::getSingleton('checkout/session')->getQuote();
	}

	public function assignData($data)
	{
		$nonce = $data['nonce'];
		$info = $this->getInfoInstance();
		$info->setAdditionalInformation( 'nonce', $nonce );

		return $this;
	}

	public function authorize(Varien_Object $payment, $amount)
	{
		parent::authorize($payment, $amount);

		if ($amount > 0){
			$this->createCharge($payment, $amount, false);
		}

		return $this;
	}

	public function capture(Varien_Object $payment, $amount)
	{
		parent::capture($payment, $amount);

		if ($amount > 0){
			$this->createCharge($payment, $amount, true);
		}

		return $this;
	}

	public function createCharge(Varien_Object $payment, $amount, $capture )
	{
		$info = $this->getInfoInstance();
		$nonce = $info->getAdditionalInformation('nonce');
		$location_id = 'F6X33Y9A4ECSM';
		$access_token = 'sq0atp-c1gfj5kRgwXPMmbTokHF4g';

		$stream = fopen( Mage::getBaseDir() .'/tmp/tmp.txt', 'a+' );
		fwrite( $stream, "nonce is: ". $nonce ."\n" );
		fclose( $stream );

		try {

			$order = $payment->getOrder();
			$amount = $order->getGrandTotal();

			$cents = 100;

			require Mage::getBaseDir().'/lib/Nvncbl/Square/autoload.php';
			$transaction_api = new \SquareConnect\Api\TransactionApi();

			$request_body = array (
				"card_nonce" => $nonce,

				# Monetary amounts are specified in the smallest unit of the applicable currency.
				# This amount is in cents. It's also hard-coded for $1, which is not very useful.
				"amount_money" => array (
				"amount" => round($amount * $cents),
				"currency" => 'USD'
				//,"description" => "Order #".$order->getRealOrderId().' by '.$order->getCustomerName(),
				),

				# Every payment you process for a given business have a unique idempotency key.
				# If you're unsure whether a particular payment succeeded, you can reattempt
				# it with the same idempotency key without worrying about double charging
				# the buyer.
				"idempotency_key" => uniqid()
			);

			# The SDK throws an exception if a Connect endpoint responds with anything besides 200 (success).
			# This block catches any exceptions that occur from the request.
			//try {
				$charge_result = $transaction_api->charge($access_token, $location_id, $request_body);
				print_r( $charge_result );
				$stream = fopen( Mage::getBaseDir() .'/tmp/tmp.txt', 'a+' );
				fwrite( $stream, 'Charge result: '. print_r( $charge_result, 1 ) ."\n" );
				fclose( $stream );
			//} catch (Exception $e) {
				$stream = fopen( Mage::getBaseDir() .'/tmp/tmp.txt', 'a+' );
				fwrite( $stream, "Caught exception " . $e->getMessage() ."\n" );
				fclose( $stream );
			//	echo "Caught exception " . $e->getMessage();
			//}


			$payment->setTransactionId( $charge_result['reference_id'] );
			$payment->setAdditionalInformation( 'captured', $capture );
			$payment->setIsTransactionClosed(0);

			// Set the order status according to the configuration
			$newOrderStatus = Mage::getStoreConfig('payment/nvncbl_square/order_status');
			if (!empty($newOrderStatus))
			{
				$order->addStatusToHistory($newOrderStatus, 'Changing order status as per New Order Status configuration');
			}

			// $payment->setIsFraudDetected(true);
			// $payment->setIsTransactionPending(!$capture);


		}
		catch(Exception $e)
		{
			Mage::log( $e->getMessage() );
			$stream = fopen( Mage::getBaseDir() .'/tmp/tmp.txt', 'a+' );
			fwrite( $stream, "Caught exception " . $e->getMessage() ."\n" );
			fclose( $stream );
			Mage::throwException($e->getMessage());
		}
	}

	protected function getSavedCardFrom(Varien_Object $payment)
	{
		$card = $payment->getAdditionalInformation('token');

		if (strstr($card, 'card_') === false)
		{
			// $cards will be NULL if the customer has no cards
			$cards = $this->getCustomerCards(true, $payment->getOrder()->getCustomerId());
			if (is_array($cards) && !empty($cards[0]))
				return $cards[0]->id;
		}

		if (strstr($card, 'card_') === false)
			return null;

		return $card;
	}

	/**
	 * Cancel payment
	 *
	 * @param   Varien_Object $invoicePayment
	 * @return  Mage_Payment_Model_Abstract
	 */
	public function cancel(Varien_Object $payment, $amount = null)
	{
		// Captured
		$creditmemo = $payment->getCreditmemo();
		if (!empty($creditmemo))
		{
			$rate = $creditmemo->getStoreToOrderRate();
			if (!empty($rate) && is_numeric($rate))
				$amount *= $rate;
		}
		// Authorized
		$amount = (empty($amount)) ? $payment->getOrder()->getTotalDue() : $amount;

		$currency = $payment->getOrder()->getOrderCurrencyCode();

		$transactionId = $payment->getParentTransactionId();
		$transactionId = preg_replace('/-.*$/', '', $transactionId);

		try {
			$cents = 100;
			if ($this->isZeroDecimal($currency))
				$cents = 1;

			$params = array(
				'amount' => round($amount * $cents)
			);
			$charge = Square_Charge::retrieve($transactionId);

			// This is true when an authorization has expired or when there was a refund through the Square account
			if (!$charge->refunded)
			{
				$charge->refund($params);

				$payment->getOrder()->addStatusToHistory(
					Mage_Sales_Model_Order::STATE_CANCELED,
					'Customer was refunded the amount of '. $amount
				);
			}
			else
			{
				Mage::throwException('This order has already been refunded in Square. To refund from Magento, please refund it offline.');
			}
		}
		catch (Exception $e)
		{
			Mage::log('Could not refund payment: '.$e->getMessage());
			Mage::throwException( 'Could not refund payment: '. $e->getMessage() );
		}

		return $this;
	}

	/**
	 * Refund money
	 *
	 * @param   Varien_Object $invoicePayment
	 * @return  Mage_Payment_Model_Abstract
	 */
	public function refund(Varien_Object $payment, $amount)
	{
		parent::refund($payment, $amount);
		$this->cancel($payment, $amount);

		return $this;
	}

	/**
	 * Void payment
	 *
	 * @param   Varien_Object $invoicePayment
	 * @return  Mage_Payment_Model_Abstract
	 */
	public function void(Varien_Object $payment)
	{
		parent::void($payment);
		$this->cancel($payment);

		return $this;
	}

}

?>