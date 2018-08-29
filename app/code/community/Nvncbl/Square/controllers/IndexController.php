<?php

require_once 'Nvncbl/Square/autoload.php';
require_once 'Nvncbl/Unirest/Unirest.php';

class Nvncbl_Square_IndexController extends Mage_Core_Controller_Front_Action {

	public function indexAction(){

		$access_token = Mage::app()->getRequest()->getParam( 'access_token' );
		$expiry = urldecode( Mage::app()->getRequest()->getParam( 'expiry' ) );
		$seed = Mage::app()->getRequest()->getParam( 'seed' );
		$salted_and_hashed = Mage::app()->getRequest()->getParam( 'salted_and_hashed' );

		$scope = Mage::app()->getRequest()->getParam( 'scope' );
		$scope_id = Mage::app()->getRequest()->getParam( 'scope_id' );

		if( $salted_and_hashed == md5( 's_a_lt'. $seed ) ){
			Mage::getConfig()->saveConfig( 'payment/nvncbl_square/personal_access_token', $access_token .'___'. $expiry, $scope, $scope_id );
			Mage::app()->getStore()->resetConfig();
			Mage::app()->getResponse()->setBody( 'Square successfully authorized.<br />Reload ( Admin Panel > System > Configuration > Payment Methods > Square Payment Gateway ) and you can now choose a Location ID' );
		}

	}

	public function removeSavedCardAction(){

		$raw_access_token = explode( '___', Mage::getStoreConfig('payment/nvncbl_square/personal_access_token') );
		$access_token = $raw_access_token[0];

		$card_id = Mage::app()->getRequest()->getParam( 'card_id' );
		$customer = Mage::getSingleton('customer/session')->getCustomer();

		try {
			SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
			$customer_api = new \SquareConnect\Api\CustomersApi();
			$customer_api->deleteCustomerCard( $customer->getSquareCustomerId(), $card_id );
			Mage::app()->getResponse()->setBody( '{ "success" : true }' );
		} catch( Exception $e ){
			Mage::log( $e->getMessage() );
			Mage::app()->getResponse()->setBody( '{ "success" : false }' );
		}

	}

	public function testListItemsAction(){

		$access_token = Mage::helper('nvncbl_square')->getAccessToken();
		$location_id = Mage::helper('nvncbl_square')->getLocationId();

		SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
		$catalog_api = new \SquareConnect\Api\CatalogApi();

		/* Obtain data from Square -- code from API docs */
		$types = join(",", array( "ITEM" ) );
		$cursor = null;
		$square_catalog_objects = array();
		do {
			$apiResponse = $catalog_api->listCatalog($cursor, $types);
			$cursor = $apiResponse['cursor'];
			if ($apiResponse['objects'] != null) {
				$square_catalog_objects = array_merge($square_catalog_objects, $apiResponse['objects']);
			}
		} while ($apiResponse['cursor']);

//		print_r( $square_catalog_objects

		/* Obtain inventory data from Square */
		$connectHost = 'https://connect.squareup.com';

		$requestHeaders = array (
			'Authorization' => 'Bearer ' . $access_token,
			'Accept' => 'application/json',
			'Content-Type' => 'application/json'
		);
		$inventoryResponse = Unirest\Request::get($connectHost . '/v1/' . $location_id . '/inventory/', $requestHeaders, json_encode(array()));

		$inventory_data = array();
		if( $inventoryResponse->code == 200 ){

			foreach( $inventoryResponse->body as $square_inventory_entry_object ){
				$inventory_data[ $square_inventory_entry_object->variation_id ] = $square_inventory_entry_object->quantity_on_hand;
			}

		}

		$collection_counter = 1;
		$matches_magento_ids = array();
		$collection = new Varien_Data_Collection();

		/* Add Square to Varien collection */
		foreach( $square_catalog_objects as $square_catalog_object ){

			$square_item = $square_catalog_object->getItemData();

			$variations = $square_item->getVariations();

			foreach( $variations as $square_catalog_object_item ){

				$square_item_variation = $square_catalog_object_item->getItemVariationData();

				$v1_id = reset( $square_catalog_object_item->getCatalogV1Ids() );

				$product = Mage::getModel('catalog/product')->loadByAttribute( 'sku', $square_item_variation->getSku() );
				if( $product ){
					$matches_magento_ids[] = $product->getId();
					$stock_item = Mage::getModel('cataloginventory/stock_item')->loadByProduct( $product );
				}

				$collection->addItem( new Varien_Object( array(
					'id' => $collection_counter, // Square Variation ID
					'square_id' => $square_catalog_object_item->getId(), // Square Variation ID
					'sku' => $square_item_variation->getSku(),
					'name' => $square_item->getName() . ( $square_item_variation->getName() != '' ? ' - '. $square_item_variation->getName() : '' ),
					'qty' => array_key_exists( is_object( $v1_id ) ? $v1_id->getCatalogV1Id() : $square_catalog_object_item->getId(), $inventory_data ) ? $inventory_data[ is_object( $v1_id ) ? $v1_id->getCatalogV1Id() : $square_catalog_object_item->getId() ] : 'Not managed',
					'entity_id' => $product ? $product->getId() : '',
					'magento_name' => $product ? $product->getName() : '',
					'magento_qty' => $product ? ( $stock_item->getManageStock() == 1 ? $stock_item->getQty() : 'Not managed' ) : ''
				) ) );

				$collection_counter++;

			}

		}

		/* Add Magneto products to Varien collection */
		$products = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect( 'name' )
			->addAttributeToFilter( 'entity_id', array( 'nin' => array( $matches_magento_ids ) ) )
			->addAttributeToFilter( 'type_id', array( 'nin' => array( 'configurable', 'grouped', 'bundle' ) ) )
			;

		foreach( $products as $product ){

			if( $product ){
				$stock_item = Mage::getModel('cataloginventory/stock_item')->loadByProduct( $product );
			}

			$collection->addItem( new Varien_Object( array(
				'id' => $collection_counter, // Square Variation ID
				'square_id' => '', // Square Variation ID
				'sku' => $product->getSku(),
				'name' => '',
				'qty' => '',
				'entity_id' => $product ? $product->getId() : '',
				'magento_name' => $product ? $product->getName() : '',
				'magento_qty' => $product ? ( $stock_item->getManageStock() == 1 ? $stock_item->getQty() : 'Not managed' ) : ''
			) ) );

			$collection_counter++;

		}

		foreach( $collection as $item ){
			print_r( $item->getData() );
			//echo $item->getName() ." with SKU: ". $item->getSku() ."\n";
		}

	}

	public function testCreateItemAction(){
exit;
		$location_id = Mage::helper('nvncbl_square')->getLocationId();
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();

		$connectHost = 'https://connect.squareup.com';

		$requestHeaders = array (
			'Authorization' => 'Bearer ' . $access_token,
			'Accept' => 'application/json',
			'Content-Type' => 'application/json'
		);

		/* Create Item */
		$request_body = array(
			"name"=>"Milkshake",
			"variations"=>array(
				array(
					"name"=>"Small",
					"pricing_type"=>"FIXED_PRICING",
					"price_money"=>array(
						"currency_code"=>"USD",
						"amount"=>400
					)
				),
				array(
					"name"=>"Medium",
					"pricing_type"=>"FIXED_PRICING",
					"price_money"=>array(
						"currency_code"=>"USD",
						"amount"=>500
					)
				),
				array(
					"name"=>"Large",
					"pricing_type"=>"FIXED_PRICING",
					"price_money"=>array(
						"currency_code"=>"USD",
						"amount"=>600
					)
				)
			)
		);
		$response = Unirest\Request::post($connectHost . '/v1/' . $location_id . '/items', $requestHeaders, json_encode($request_body));
		if ($response->code == 200) {
			echo 'Successfully created item:';
			echo json_encode($response->body, JSON_PRETTY_PRINT);

			$new_item = $response->body;
			var_dump( $new_item );

		} else {
			echo gettype( $response );
			echo 'Item creation failed';
			return NULL;
		}
		/* END Create Item */

	}

	public function testUpdateInventoryAction(){

		$location_id = Mage::helper('nvncbl_square')->getLocationId();
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();

		$connectHost = 'https://connect.squareup.com';

		$requestHeaders = array (
			'Authorization' => 'Bearer ' . $access_token,
			'Accept' => 'application/json',
			'Content-Type' => 'application/json'
		);

		/*
		Small - 95acbabe-1773-499d-a85c-77895d3776aa
		Medium - 5257c30a-8439-4d20-83b9-3e6abbf93f6c
		Large - 931366a7-951c-4a51-92fe-93ed97dff75c
		*/
		$variation_id = '95acbabe-1773-499d-a85c-77895d3776aa';

		$adjustment_type = 'MANUAL_ADJUST'; // Also 'SALE' or 'RECEIVE_STOCK'

		if( true ){

			/* Create Item */
			$request_body = array(
				'quantity_delta' => '10',
				'adjustment_type' => $adjustment_type,
				'memo' => 'This is an adjustment'
			);
			$response = Unirest\Request::post($connectHost . '/v1/' . $location_id . '/inventory/'. $variation_id .'', $requestHeaders, json_encode($request_body));
			if ($response->code == 200) {

				echo 'Success';

				var_dump( $response->body );

			} else {

				echo 'Failure';

				var_dump( $response->body );
			}
			/* END Create Item */

		}

	}

	public function testListInventoryAction(){

		$location_id = Mage::helper('nvncbl_square')->getLocationId();
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();

		$connectHost = 'https://connect.squareup.com';

		$requestHeaders = array (
			'Authorization' => 'Bearer ' . $access_token,
			'Accept' => 'application/json',
			'Content-Type' => 'application/json'
		);

		$inventoryResponse = Unirest\Request::get($connectHost . '/v1/' . $location_id . '/inventory/', $requestHeaders, json_encode(array()));

		$inventory_data = array();
		if( $inventoryResponse->code == 200 ){

			foreach( $inventoryResponse->body as $square_inventory_entry_object ){
				$inventory_data[ $square_inventory_entry_object->variation_id ] = $square_inventory_entry_object->quantity_on_hand;
			}

		}

	}

}