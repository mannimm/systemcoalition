<?php

require_once 'Nvncbl/Square/autoload.php';

//require_once Mage::getBaseDir() .'/lib/Nvncbl/Square/autoload.php';

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
	protected $_canVoid                 = false;
	protected $_canCancelInvoice        = false;
	protected $_canUseInternal          = true;
	protected $_canUseCheckout          = true;
	protected $_canSaveCc               = false;
	protected $_formBlockType           = 'nvncbl_square/form_standard';

	const TEST = 'test';
	const LIVE = 'live';

	const ACTION_TOKENIZE = 'tokenize';

	public function assignData( $data ){

		$nonce = $data['nonce'];
		$info = $this->getInfoInstance();
		$info->setAdditionalInformation( 'nonce', $nonce );

		$save_cc_yesno = $data['save-cc-yesno'];
		$info = $this->getInfoInstance();
		$info->setAdditionalInformation( 'save-cc-yesno', $save_cc_yesno );

		$saved_cc_id = $data['saved_cc_id'];
		$info = $this->getInfoInstance();
		$info->setAdditionalInformation( 'saved_cc_id', $saved_cc_id );

		return $this;

	}

	public function getConfigPaymentAction(){
		$payment_action = parent::getConfigPaymentAction();

		if( $payment_action == Nvncbl_Square_Model_Standard::ACTION_TOKENIZE ){
			return 'authorize';
		}

		return $payment_action;
	}

	public function authorize( Varien_Object $payment, $amount ){

		parent::authorize( $payment, $amount );

		try {
			if( $amount > 0 ){
				$this->charge( $payment, $amount, false );
			}
		} catch( Exception $e ){
			Mage::log( $e->getMessage() );
			//Mage::throwException( $e->getMessage() );

			//$message = '[HTTP/1.1 402 Payment Required] {"errors":[{"category":"PAYMENT_METHOD_ERROR","code":"CARD_DECLINED","detail":"Card declined."}]}';
			$message = $e->getMessage();
			$message = substr( $message, strpos( $message, ']' ) + 1 );

			$message = json_decode( $message );
			foreach( $message->errors as $error ){
				Mage::throwException( $error->detail );
			}
			Mage::throwException( $e->getMessage() );
		}

		return $this;
	}

	public function capture( Varien_Object $payment, $amount ){

		parent::capture($payment, $amount);

		try {
			if( $amount > 0 ){

				$last_trans_id = $payment->getLastTransId();
				if(
					empty( $last_trans_id )
					|| ( parent::getConfigPaymentAction() == 'tokenize' )
				){ // Brand new auth_capture
					$this->charge($payment, $amount, true);
				} else {
					$location_id = Mage::helper('nvncbl_square')->getLocationId( $payment->getOrder()->getStoreId() );
					$access_token = Mage::helper('nvncbl_square')->getAccessToken();

					SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
					$api = new \SquareConnect\Api\TransactionsApi();
					$api->captureTransaction( $location_id, $last_trans_id );
				}
			}
		} catch( Exception $e ){
			Mage::log( $e->getMessage() );
			//Mage::throwException( $e->getMessage() );

			//$message = '[HTTP/1.1 402 Payment Required] {"errors":[{"category":"PAYMENT_METHOD_ERROR","code":"CARD_DECLINED","detail":"Card declined."}]}';
			$message = $e->getMessage();
			$message = substr( $message, strpos( $message, ']' ) + 1 );

			$message = json_decode( $message );
			foreach( $message->errors as $error ){
				//Mage::throwException( $message );
				Mage::throwException( $error->detail );
			}
			Mage::throwException( $e->getMessage() );
		}

		return $this;
	}

	public function charge( Varien_Object $payment, $amount, $capture ){

		$info = $this->getInfoInstance();
		$original_payment_action = parent::getConfigPaymentAction();

		$nonce = $info->getAdditionalInformation('nonce');
		$save_cc_yesno = $info->getAdditionalInformation('save-cc-yesno');
		$saved_cc_id = $info->getAdditionalInformation('saved_cc_id');

		$order = $payment->getOrder();

		$customer_card_id = false;
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();
		$location_id = Mage::helper('nvncbl_square')->getLocationId( $payment->getOrder()->getStoreId() );
		$customer = Mage::getModel('customer/customer')->load( $order->getCustomerId() );

		$billing_address = $order->getBillingAddress();

		try {

			if( !$customer->getSquareCustomerId() ){

				// Create square customer and save its ID
				SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
				$customer_api = new \SquareConnect\Api\CustomersApi();

				$create_customer_body = array(
				  'given_name' => $billing_address->getFirstname(),
				  'family_name' => $billing_address->getLastname(),
				  'email_address' => $billing_address->getEmail(),
				  'address' => array(
					'address_line_1' => $billing_address->getStreet1(),
					'address_line_2' => $billing_address->getStreet2(),
					'locality' => $billing_address->getCity(),
					'administrative_district_level_1' => Mage::getModel('directory/region')->load( $billing_address->getRegionId() )->getCode(),
					'postal_code' => $billing_address->getPostcode(),
					'country' => $billing_address->getCountryId()
				  ),
				  'phone_number' => $billing_address->getTelephone(),
				  'reference_id' => '',
				  'note' => ''
				);

				//Mage::log( "create customer body: ". print_r( $create_customer_body, 1 ) );

				$create_customer_result = $customer_api->createCustomer( $create_customer_body );

				$square_customer_id = $create_customer_result->getCustomer()->getId();
				$info->setAdditionalInformation( 'square_customer_id', $square_customer_id );
				$customer->setSquareCustomerId( $square_customer_id );
				if( $customer->getId() ){
					$customer->save();
				}

			}

		} catch( Exception $e ){
			Mage::log( $e->getMessage() );
		}

		/* Check to see if submitting a new saved CC */
		/* Also check to see if action is to tokenize, in which case we need to save the token */
		/* Want to create customer and card ID before transaction */
		if(
			( $save_cc_yesno )
			|| ( $original_payment_action == 'tokenize' )
		){

			try {

				// Save card
				SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
				$customer_api = new \SquareConnect\Api\CustomersApi();
				$create_customer_card_body = array(
				  'card_nonce' => $nonce,
				  'billing_address' => array(
					'address_line_1' => $billing_address->getStreet1(),
					'address_line_2' => $billing_address->getStreet2(),
					'locality' => $billing_address->getCity(),
					'administrative_district_level_1' => Mage::getModel('directory/region')->load( $billing_address->getRegionId() )->getCode(),
					'postal_code' => $billing_address->getPostcode(),
					'country' => $billing_address->getCountryId()
				  ),
				  'cardholder_name' => $billing_address->getFirstname() .' '. $billing_address->getLastname()
				);

				//Mage::log( '$create_customer_card_customer_id: '. $customer->getSquareCustomerId() );
				//Mage::log( '$create_customer_card_body: '. print_r( $create_customer_card_body, 1 ) );

				$create_customer_card_result = $customer_api->createCustomerCard( $customer->getSquareCustomerId(), $create_customer_card_body );

				if( $create_customer_card_result->getCard() ){
					//Mage::log( "card: ". $create_customer_card_result->getCard()->getId() );
					$customer_card_id = $create_customer_card_result->getCard()->getId();
					if( $original_payment_action == 'tokenize' ){
						$info->setAdditionalInformation( 'saved_cc_id', $customer_card_id );
					}
				}

				if( $create_customer_card_result->getErrors() ){
					foreach( $create_customer_card_result->getErrors() as $error ){
						Mage::log( "error: ". $error->getCode() );
					}
				}

			} catch( Exception $e ){
				Mage::log( $e->getMessage() );
			}

		}

		$billing_address = $order->getBillingAddress();

		/* Now the transaction */
		if(
			$original_payment_action != 'tokenize'
			|| ( $original_payment_action == 'tokenize' && ( $capture ) )
		){

			//require_once( Mage::getBaseDir().'/lib/Nvncbl/Square/autoload.php' );
			SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
			$transaction_api = new \SquareConnect\Api\TransactionsApi();

			$supported_currencies = Mage::helper('nvncbl_square')->getSupportedCurrencies();
			$transaction_currency = $order->getOrderCurrencyCode();
			//$transaction_amount = round( $order->getGrandTotal() * $supported_currencies[ $transaction_currency ]['num_base_units'] );
			$transaction_amount = round( $amount * $supported_currencies[ $transaction_currency ]['num_base_units'] );

			if( $order->getOrderCurrencyCode() != Mage::getStoreConfig('payment/nvncbl_square/currency') ){

				$transaction_currency = Mage::getStoreConfig('payment/nvncbl_square/currency');

				//$transaction_amount = round( Mage::helper('directory')->currencyConvert( $order->getGrandTotal(), $transaction_currency, Mage::getStoreConfig('payment/nvncbl_square/currency') ) * $supported_currencies[ $transaction_currency ]['num_base_units'] );
				$transaction_amount = round( Mage::helper('directory')->currencyConvert( $amount, $transaction_currency, Mage::getStoreConfig('payment/nvncbl_square/currency') ) * $supported_currencies[ $transaction_currency ]['num_base_units'] );

			}

			$request_body = array(
				'amount_money' => array(
					'amount' => $transaction_amount,
					'currency' => $transaction_currency
				),
				'idempotency_key' => uniqid(),
				'buyer_email_address' => $order->getCustomerEmail(),
				'billing_address' => array(
					'address_line_1' => $billing_address->getStreet1(),
					'address_line_2' => $billing_address->getStreet2(),
					'locality' => $billing_address->getCity(),
					'administrative_district_level_1' => $billing_address->getRegion(),
					'postal_code' => $billing_address->getPostcode(),
					'country' => $billing_address->getCountryId(),
				)
			);

			if( $saved_cc_id ){
				$request_body['customer_id'] = $customer->getSquareCustomerId();
				$request_body['customer_card_id'] = $saved_cc_id;
			} else if( !$customer_card_id ){
				$request_body['customer_id'] = $customer->getSquareCustomerId();
				$request_body['card_nonce'] = $nonce;
			} else {
				$request_body['customer_id'] = $customer->getSquareCustomerId();
				$request_body['customer_card_id'] = $customer_card_id;
			}

			if( ( $shipping_address = $order->getShippingAddress() ) !== false ){
				$request_body['shipping_address'] = array(
					'address_line_1' => $shipping_address->getStreet1(),
					'address_line_2' => $shipping_address->getStreet2(),
					'locality' => $shipping_address->getCity(),
					'administrative_district_level_1' => $shipping_address->getRegion(),
					'postal_code' => $shipping_address->getPostcode(),
					'country' => $shipping_address->getCountryId(),
				);
			}

			if( !$capture ){
				$request_body['delay_capture'] = true;
			}

			//$request_body['reference_id'] = Mage::getSingleton('checkout/session')->getQuote()->reserveOrderId()->save()->getReservedOrderId();
			$request_body['note'] = $order->getIncrementId();
			$request_body['reference_id'] = $order->getIncrementId();

			Mage::log( "request body: ". print_r( $request_body, 1 ) );

			# The SDK throws an exception if a Connect endpoint responds with anything besides 200 (success).
			$charge_result = $transaction_api->charge( $location_id, $request_body);

			/* Process the result of the charge */
			/* Save tender ID for later capture or potential refund */
			foreach( $charge_result->getTransaction()->getTenders() as $tender ){
				$payment->setCcType( $tender->getCardDetails()->getCard()->getCardBrand() );
				$payment->setCcLast4( $tender->getCardDetails()->getCard()->getLast4() );
				$info->setAdditionalInformation( 'tender_id', $tender->getId() );
			}

			$payment->setLastTransId( $charge_result->getTransaction()->getId() );

		}

		$newOrderStatus = Mage::getStoreConfig('payment/nvncbl_square/order_status');
		if (!empty($newOrderStatus)){
			$order->addStatusToHistory($newOrderStatus, 'New Order Status');
		}

	}

	public function cancel( Varien_Object $payment, $amount = null ){

		$info = $this->getInfoInstance();

		$tender_id = $info->getAdditionalInformation('tender_id');
		$location_id = Mage::helper('nvncbl_square')->getLocationId( $payment->getOrder()->getStoreId() );
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();

		// Captured
		$creditmemo = $payment->getCreditmemo();
		if (!empty($creditmemo)){
			$rate = $creditmemo->getStoreToOrderRate();
			if (!empty($rate) && is_numeric($rate)){
				$amount *= $rate;
			}
		}
		// Authorized
		$amount = (empty($amount)) ? $payment->getOrder()->getTotalDue() : $amount;

		$supported_currencies = Mage::helper('nvncbl_square')->getSupportedCurrencies();
		$currency = $payment->getOrder()->getOrderCurrencyCode();

		$transactionId = $payment->getLastTransId();
		$transactionId = preg_replace('/-.*$/', '', $transactionId);

		try {

			$transaction_amount = $amount * $supported_currencies[ $currency ]['num_base_units'];

			SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
			$api = new \SquareConnect\Api\TransactionsApi();

			$idempotencyKey = uniqid();

			$refundResult = $api->createRefund( $location_id, $transactionId, array(
				'tender_id' => $tender_id,
				'amount_money' => array(
					'amount' => $transaction_amount,
					'currency' => 'USD'
				),
				'idempotency_key' => $idempotencyKey,
				'reason' => 'Cancelled order'
			));

			if( in_array( $refundResult->getRefund()->getStatus(), array( 'PENDING', 'APPROVED' ) ) ){
				$payment->getOrder()->addStatusToHistory( Mage_Sales_Model_Order::STATE_CANCELED, 'Customer was refunded the amount of '. number_format( $transaction_amount / $supported_currencies[ $currency ]['num_base_units'], 2 ) );
			}

		} catch( Exception $e ){
			Mage::log( 'Could not refund payment: '. $e->getMessage() );
			Mage::throwException( 'Could not refund payment: ' . $e->getMessage() );
		}

		return $this;
	}

	public function refund(Varien_Object $payment, $amount){
		parent::refund($payment, $amount);
		$this->cancel($payment, $amount);

		return $this;
	}

	public function void(Varien_Object $payment){
		parent::void($payment);
		$this->cancel($payment);

		return $this;
	}

}