<?php

class Nvncbl_Square_Block_Adminhtml_Oauthurl extends Mage_Adminhtml_Block_System_Config_Form_Field {

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$element->setAfterElementHtml( $this->_getAfterElementHtml( $element ) );
		return $element->getElementHtml();
	}

	protected function _getAfterElementHtml( $element ){
		$client_id = 'sq0idp-h-HXwQh2bzA1EE1mL_kXfQ';
		$scope = 'MERCHANT_PROFILE_READ PAYMENTS_READ PAYMENTS_WRITE CUSTOMERS_READ CUSTOMERS_WRITE ITEMS_READ ITEMS_WRITE';

		$seed = uniqid();
		$salted_and_hashed = md5( 's_a_lt'. $seed );

		$raw_existing_access_token = explode( '___', $element->getValue() );
		$existing_access_token = $raw_existing_access_token[0];
		$existing_access_token_expiry = str_replace( 'Z', '', str_replace( 'T', ' ', $raw_existing_access_token[1] ) );

		$websiteCode = Mage::app()->getRequest()->getParam('website');
		$storeCode = Mage::app()->getRequest()->getParam('store');

		$magento_scope = 'default';
		$magento_scope_id = 0;

		if( $storeCode ){
			$magento_scope = 'stores';
			$magento_scope_id = Mage::getModel('core/store')->load( $storeCode )->getId();
		} else if( $websiteCode ){
			$magento_scope = 'websites';
			$magento_scope_id = Mage::getModel('core/website')->load( $websiteCode )->getId();
		}

		if( $existing_access_token ){
			return 'Authorized.  Expires: '. $existing_access_token_expiry .'<br /><a href="https://connect.squareup.com/oauth2/authorize?client_id='. urlencode( $client_id ) .'&scope='. urlencode( $scope ) .'&state='. urlencode( Mage::getStoreConfig('web/unsecure/base_url', 1) ) .'_'. $seed .'_'. $salted_and_hashed .'_'. $magento_scope .'_'. $magento_scope_id .'" target="_new">Click to re-authorize</a>';
		} else {
			return '<a href="https://connect.squareup.com/oauth2/authorize?client_id='. urlencode( $client_id ) .'&scope='. urlencode( $scope ) .'&state='. urlencode( Mage::getStoreConfig('web/unsecure/base_url', 1) ) .'_'. $seed .'_'. $salted_and_hashed .'_'. $magento_scope .'_'. $magento_scope_id .'" target="_new">Click here to authorize this extension with Square</a>';
		}

	}

}
