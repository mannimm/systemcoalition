<?php

class Nvncbl_Square_Helper_Data extends Mage_Payment_Helper_Data {

	public function getMode(){
		return Mage::getStoreConfig('payment/nvncbl_square/square_mode');
	}

	public function getApplicationId(){
		if( $this->getMode() == 'live' ){
			return 'sq0idp-h-HXwQh2bzA1EE1mL_kXfQ';
		} else {
			return 'sandbox-sq0idp-h-HXwQh2bzA1EE1mL_kXfQ';
		}
	}

	public function getAccessToken(){
		if( $this->getMode() == 'live' ){
			$raw_access_token = explode( '___', Mage::getStoreConfig('payment/nvncbl_square/personal_access_token') );
			return $raw_access_token[0];
		} else {
			return 'sandbox-sq0atb-n5mhPAUKWnTHEAMzotnKUw';
		}
	}

	public function getLocationId( $scope = 0 ){
		if( $this->getMode() == 'live' ){
			return Mage::getStoreConfig('payment/nvncbl_square/location_id', $scope);
		} else {
			return Mage::getStoreConfig('payment/nvncbl_square/testing_location_id');
		}
	}

	public function getEnableSavedCc(){
		if( Mage::getStoreConfig('payment/nvncbl_square/enable_saved_cc') ){
			return true;
		}
		return false;
	}

	public function getEnableApplePay(){
		if( Mage::getStoreConfig('payment/nvncbl_square/enable_apple_pay') ){
			return true;
		}
		return false;
	}

	public function getSupportedCurrencies(){
		$supported_currencies = array(
			'AUD' => array(
				'code' => 'AUD',
				'label' => 'Australian Dollar',
				'num_base_units' => 100
			),
			'CAD' => array(
				'code' => 'CAD',
				'label' => 'Canadian Dollar',
				'num_base_units' => 100
			),
			'GBP' => array(
				'code' => 'GBP',
				'label' => 'British Pound',
				'num_base_units' => 100
			),
			'JPY' => array(
				'code' => 'JPY',
				'label' => 'Japanese Yen',
				'num_base_units' => 1
			),
			'USD' => array(
				'code' => 'USD',
				'label' => 'United States Dollar',
				'num_base_units' => 100
			)
		);
		return $supported_currencies;
	}

	public function getCatalogSyncSettings(){
		return json_decode( Mage::getStoreConfig( 'payment/nvncbl_square/catalog_sync_settings' ), true );
	}

	public function getCatalogSyncAttributes(){
		$sync_attributes = array(
			'name' => 'Name',
			'description' => 'Description',
			'price' => 'Price',
			'manage_stock' => 'Manage Stock',
			'qty' => 'Qty'
		);
		return $sync_attributes;
	}

	public function getBillingAddress( $quote = null ){
		$address = array();
		$checkout = Mage::getSingleton('checkout/session')->getQuote();
		if( $checkout->getItemsCount() > 0 ){
			$billAddress = $checkout->getBillingAddress();
			$address['address_line1'] = $billAddress->getData('street');
			$address['address_zip'] = $billAddress->getData('postcode');
		}
		if( empty( $address['address_line1'] ) && Mage::app()->getStore()->isAdmin() ){
			$quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();

			if( !empty( $quote ) ){
				$billAddress = $quote->getBillingAddress();
				$address['address_line1'] = $billAddress->getData('street');
				$address['address_zip'] = $billAddress->getData('postcode');
			}
		} else if( empty( $address['address_line1']) ){
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			$customerAddressId = $customer->getDefaultBilling();
			if( $customerAddressId ){
				$billAddress = Mage::getModel('customer/address')->load($customerAddressId);
				$address['address_line1'] = $billAddress->getData('street');
				$address['address_zip'] = $billAddress->getData('postcode');
			}
		}
		return $address;
	}

}