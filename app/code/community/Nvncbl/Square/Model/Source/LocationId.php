<?php

require_once 'Nvncbl/Square/autoload.php';

class Nvncbl_Square_Model_Source_LocationId
{
	public function toOptionArray()
	{
		$websiteCode = Mage::app()->getRequest()->getParam('website');
		$storeCode = Mage::app()->getRequest()->getParam('store');

		$magento_scope = 'default';
		$magento_scope_id = 0;

		$raw_access_token = explode( '___', Mage::getStoreConfig('payment/nvncbl_square/personal_access_token' ) );
		if( $storeCode ){
			$magento_scope = 'stores';
			$magento_scope_id = Mage::getModel('core/store')->load( $storeCode )->getId();
			$raw_access_token = explode( '___', Mage::app()->getStore( $magento_scope_id )->getConfig('payment/nvncbl_square/personal_access_token' ) );
		} else if( $websiteCode ){
			$magento_scope = 'websites';
			$magento_scope_id = Mage::getModel('core/website')->load( $websiteCode )->getId();
			$raw_access_token = explode( '___', Mage::app()->getWebsite( $magento_scope_id )->getConfig('payment/nvncbl_square/personal_access_token' ) );
		}

		$access_token = $raw_access_token[0];

		$output = array(
			array(
				'value' => '',
				'label' => ' -- None selected --'
			)
		);
		if( $access_token ){

			try {

				SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);

				$location_api = new \SquareConnect\Api\LocationsApi();

				$result = $location_api->listLocations();
				foreach( $result->getLocations() as $location ){
					$output[] = array(
						'value' => $location->getId(),
						'label' => $location->getName() //.' via token: '. $access_token
					);
				}
			} catch( Exception $e ){
				Mage::log( $e->getMessage() );
			}
		}

		return $output;
	}
}
