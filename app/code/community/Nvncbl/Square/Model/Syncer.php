<?php

require_once 'Nvncbl/Square/autoload.php';
require_once 'Nvncbl/Unirest/Unirest.php';

class Nvncbl_Square_Model_Syncer extends Varien_Object {

	var $_collection;
	var $_matched_magento_ids = array();
	var $_associated_simples = array();

	public function getCollection( $actually_execute = false ){

		if( is_null( $this->_collection ) || $actually_execute ){
			/*
				Build a $collection
				Starting with fetching items from Square
					Add them to the collection along with any other corresponding Magento product data
				Then add all remaining Magento products that weren't fetched
				Thusly all products should be represented
			*/

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
			$collection = new Varien_Data_Collection();

			/* Add Square to Varien collection */
			foreach( $square_catalog_objects as $square_catalog_object ){

				$square_item = $square_catalog_object->getItemData();

				$variations = $square_item->getVariations();
				if( count( $variations ) > 1 ){
					$collection_counter++;
				}

				$collection_items_to_add = array();
				foreach( $variations as $square_catalog_object_item ){

					$square_item_variation = $square_catalog_object_item->getItemVariationData();

					$v1_id = reset( $square_catalog_object_item->getCatalogV1Ids() );

					$product = Mage::getModel('catalog/product')->loadByAttribute( 'sku', $square_item_variation->getSku() );
					if( $product ){
						$this->_matched_magento_ids[] = $product->getId();
						$stock_item = Mage::getModel('cataloginventory/stock_item')->loadByProduct( $product );
					}

					$collection_item = new Varien_Object( array(
						'id' => $collection_counter,
						'square_id' => $square_catalog_object->getId(), // Square ID
						'square_variation_id' => $square_catalog_object_item->getId(), // Square Variation ID
						'sku' => $square_item_variation->getSku(),
						'price' => is_object( $square_item_variation->getPriceMoney() ) ? number_format( $square_item_variation->getPriceMoney()->getAmount() / 100, 2 ) : '',
						//'name' => $square_item->getName() . ( $square_item_variation->getName() != '' ? ' - '. $square_item_variation->getName() : '' ),
						'name' => $square_item_variation->getName() ? $square_item_variation->getName() : $square_item->getName(),
						'description' => $square_item->getDescription(),
						//'name' => $square_item->getImageUrl(),
						'qty' => array_key_exists( is_object( $v1_id ) ? $v1_id->getCatalogV1Id() : $square_catalog_object_item->getId(), $inventory_data ) ? (string) $inventory_data[ is_object( $v1_id ) ? $v1_id->getCatalogV1Id() : $square_catalog_object_item->getId() ] : 'Not managed',
						'entity_id' => $product ? $product->getId() : '',
						'magento_name' => $product ? $product->getName() : '',
						'magento_description' => $product ? $product->getDescription() : '',
						'magento_price' => $product ? number_format( $product->getPrice(), 2 ) : '',
						'magento_qty' => $product ? ( $stock_item->getManageStock() == 1 ? (string) $stock_item->getQty() : 'Not managed' ) : ''
					) );

					$collection_items_to_add[] = $collection_item;

					$collection_counter++;

				}

				if( count( $collection_items_to_add ) == 1 ){
					$collection_item = reset( $collection_items_to_add );
					/* Simples */
					$collection->addItem( $collection_item );
					$this->processRowChanges( $collection_item, $actually_execute );
				} else {

					/* Configurable representation in Square */
					$parent_ids = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild( $collection_item->getEntityId() );
					$config_product_id = reset( $parent_ids );
					$config_product = Mage::getModel('catalog/product')->load( $config_product_id );
					$this->_matched_magento_ids[] = $config_product_id;
					$collection_item = new Varien_Object( array(
						'id' => ( $collection_counter - count( $collection_items_to_add ) - 1 ),
						'square_id' => $collection_item->getSquareId(), // Square ID
						'square_variation_id' => '', // Square Variation ID
						'sku' => $config_product->getSku(),
						'price' => '',
						'name' => $square_item->getName(),
						'description' => $square_item->getDescription(),
						'qty' => '',
						'entity_id' => $config_product_id,
						'magento_name' => $config_product->getName(),
						'magento_description' => $config_product ? $config_product->getDescription() : '',
						'magento_price' => $config_product->getPrice(),
						'magento_qty' => ''
					) );
					$collection->addItem( $collection_item );
					$this->processRowChanges( $collection_item, $actually_execute );

					foreach( $collection_items_to_add as $collection_item ){
						/* Simples */
						$collection->addItem( $collection_item );
						$this->_associated_simples[] = $collection_item->getEntityId();
						$this->processRowChanges( $collection_item, $actually_execute );

					}
				}

			}

			/* Add Magento products to Varien collection */
			/* These products are not yet represented in Square */
			$products = Mage::getResourceModel('catalog/product_collection')
				->addAttributeToSelect( 'name' )
				->addAttributeToSelect( 'price' )
				->addAttributeToSelect( 'description' )
				->addAttributeToFilter( 'entity_id', array( 'nin' => array( $this->_matched_magento_ids ) ) )
				->addAttributeToFilter( 'type_id', array( 'nin' => array( 'grouped', 'bundle' ) ) )
				;
			$products->getSelect()->order( new Zend_Db_Expr( ' CASE WHEN type_id = "configurable" THEN 0 ELSE 1 END ' ) );

			foreach( $products as $product ){

				if( $product ){
					$stock_item = Mage::getModel('cataloginventory/stock_item')->loadByProduct( $product );
				}

				if( $product->getTypeId() == 'configurable' ){
					$children_ids = array_keys( reset( Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getId()) ) );
	//				print_r( $children_ids );
					$this->_associated_simples = array_merge( $this->_associated_simples, $children_ids );
				}

				$collection_item = new Varien_Object( array(
					'id' => $collection_counter,
					'square_id' => '', // Square Variation ID
					'square_variation_id' => '', // Square Variation ID
					'sku' => $product->getSku(),
					'price' => '',
					'name' => '',
					'description' => '',
					'qty' => '',
					'entity_id' => $product ? $product->getId() : '',
					'magento_name' => $product ? $product->getName() : '',
					'magento_description' => $product ? $product->getDescription() : '',
					'magento_price' => $product ? number_format( $product->getPrice(), 2 ) : '',
					'magento_qty' => $product ? ( $stock_item->getManageStock() == 1 ? (string) $stock_item->getQty() : 'Not managed' ) : ''
				) );

				$collection->addItem( $collection_item );

				$this->processRowChanges( $collection_item, $actually_execute );

				$collection_counter++;

			}
			$this->_collection = $collection;
		}

		return $this->_collection;
	}

	public function processRowChanges( $item, $actually_execute = false ){

		$changes = array();
		$upsert = false;

		$catalog_sync_settings = new Varien_Object( Mage::helper('nvncbl_square')->getCatalogSyncSettings() );

		if( $item->getEntityId() || $item->getSku() ){

			/* Manage Product Existence */
			if( !$item->getEntityId() ){
				if( $catalog_sync_settings->getUpsertMagento() == '1' ){
					$upsert = true;
					$changes[] = 'upsert_magento';
					if( $actually_execute ){
						$this->upsertMagento( $item );
					}
				}
			}

			if(
				!$item->getSquareId()
				&& !in_array( $item->getEntityId(), $this->_associated_simples )
			){
				if( $catalog_sync_settings->getUpsertSquare() == '1' ){
					$upsert = true;
					$changes[] = 'upsert_square';
					if( $actually_execute ){
						$this->upsertSquare( $item );
					}
				}
			}

			if( !$upsert && $item->getSquareId() && $item->getEntityId() ){

				/* Manage Stock */
				if( $catalog_sync_settings->getAttributeControlManageStock() == 'magento' ){
					if( $item->getMagentoQty() === 'Not managed' && $item->getQty() !== 'Not managed' ){
						$changes[] = 'update_square_manage_stock';
						if( $actually_execute ){
							$this->updateSquareManageStock( $item );
						}
					}
				} else if( $catalog_sync_settings->getAttributeControlManageStock() == 'square' ){
					if( $item->getQty() === 'Not managed' && $item->getMagentoQty() !== 'Not managed' ){
						$changes[] = 'update_magento_manage_stock';
						if( $actually_execute ){
							$this->updateMagentoManageStock( $item );
						}
					} else if( $item->getMagentoQty() === 'Not managed' && is_numeric( $item->getQty() ) ){
						$changes[] = 'update_magento_manage_stock';
						if( $actually_execute ){
							$this->updateMagentoManageStock( $item );
						}
					}
				}

				/* Manage Qty */
				if( $catalog_sync_settings->getAttributeControlQty() == 'magento' ){
					if( $item->getQty() != $item->getMagentoQty() ){
						$changes[] = 'update_square_qty';
						if( $actually_execute ){
							$this->updateSquareQty( $item->getSquareVariationId(), ( $item->getMagentoQty() - $item->getQty() ) );
						}
					}
				} else if( $catalog_sync_settings->getAttributeControlQty() == 'square' ){
					if( $item->getQty() != $item->getMagentoQty() ){
						$changes[] = 'update_magento_qty';
						if( $actually_execute ){
							$this->updateMagentoQty( $item );
						}
					}
				}

				/* Manage Price */
				if( $catalog_sync_settings->getAttributeControlPrice() == 'magento' ){
					if( $item->getMagentoPrice() != $item->getPrice() ){
						if( $item->getSquareVariationId() ){
							$changes[] = 'update_square_price';
							if( $actually_execute ){
								$this->updateSquarePrice( $item );
							}
						}
					}
				} else if( $catalog_sync_settings->getAttributeControlPrice() == 'square' ){
					if( $item->getMagentoPrice() != $item->getPrice() ){
						$changes[] = 'update_magento_price';
						if( $actually_execute ){
							$this->updateMagentoPrice( $item );
						}
					}
				}

				/* Manage Name */
				if( $catalog_sync_settings->getAttributeControlName() == 'magento' ){
					if( $item->getMagentoName() != $item->getName() ){
						$changes[] = 'update_square_name';
						if( $actually_execute ){
							$this->updateSquareName( $item );
						}
					}
				} else if( $catalog_sync_settings->getAttributeControlName() == 'square' ){
					if( $item->getMagentoName() != $item->getName() ){
						$changes[] = 'update_magento_name';
						if( $actually_execute ){
							$this->updateMagentoName( $item );
						}
					}
				}
				/* Manage Description */
				if( !in_array( $item->getEntityId(), $this->_associated_simples ) ){
					if( $catalog_sync_settings->getAttributeControlDescription() == 'magento' ){
						if( $item->getMagentoDescription() != $item->getDescription() ){
							$changes[] = 'update_square_description';
							if( $actually_execute ){
								$this->updateSquareDescription( $item );
							}
						}
					} else if( $catalog_sync_settings->getAttributeControlDescription() == 'square' ){
						if( $item->getMagentoDescription() != $item->getDescription() ){
							$changes[] = 'update_magento_description';
							if( $actually_execute ){
								$this->updateMagentoDescription( $item );
							}
						}
					}
				}

			}

		} else {
			//$changes[] = 'ignoring -- missing SKU';
		}

		$item->setChanges( $changes );
		$item->setChangesLabel( implode( "\n", $changes ) );

	}

	public function upsertMagento( $item ){

		$product = Mage::getModel('catalog/product');

		$product->setSku( $item->getSku() );
		$product->setTypeId( 'simple' );
		$product->setStatus( 1 );
		$product->setWebsiteIds( array( 1 ) );
		$product->setAttributeSetId( 4 );
		$product->setTaxClassId( 0 );
		$product->setCreatedAt( strtotime('now') );
		$product->setVisibility( 4 );

		$product->setName( $item->getName() );
		$product->setDescription( $item->getName() );
		$product->setShortDescription( $item->getName() );
		$product->setWeight( 1.00 );

		$product->setPrice( $item->getPrice() );

		$product->save();

		$this->_matched_magento_ids[] = $product->getId();

		$stockItem = Mage::getModel('cataloginventory/stock_item');
		$stockItem->loadByProduct( $product->getId() );
		$stockItem->setUseConfigManageStock( false );

		if( !$stockItem->getId() ){
			$stockItem->setProductId( $product->getId() )->setStockId( 1 );
			if( $item->getQty() !== 'Not managed' ){
				$stockItem->setManageStock( true );
				$stockItem->setQty( $item->getQty() );
				$stockItem->setIsInStock( 1 );
			} else {
				$stockItem->setManageStock( false );
				$stockItem->setIsInStock( 1 );
			}
			$stockItem->save(); // need this line and the next line or else it won't work
			$stockItem->loadByProduct( $product->getId() ); // kind of like it refreshes the object.  IDFK!
		}

		if( $item->getQty() !== 'Not managed' ){
			$stockItem->setManageStock( true );
			$stockItem->setQty( $item->getQty() );
			$stockItem->setIsInStock( 1 );
		} else {
			$stockItem->setManageStock( false );
			$stockItem->setIsInStock( 1 );
		}

		$stockItem->save();

	}

	public function upsertSquare( $item ){

		$location_id = Mage::helper('nvncbl_square')->getLocationId();
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();

		$connectHost = 'https://connect.squareup.com';

		$requestHeaders = array (
			'Authorization' => 'Bearer ' . $access_token,
			'Accept' => 'application/json',
			'Content-Type' => 'application/json'
		);

		$product_id = $item->getEntityId();
		$product = Mage::getModel('catalog/product')->load( $product_id );

		if( $product->getTypeId() != 'configurable' ){

			$parent_ids = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild( $item->getEntityId() );

			if( count( $parent_ids ) == 0 ){ // Add as completely new item

				$request_body = array(
					'name' => $item->getMagentoName(),
					'variations' => array(
						array(
							'sku' => $item->getSku(),
							'pricing_type' => 'FIXED_PRICING',
							'price_money' => array(
								'currency_code' => 'USD',
								'amount' => $item->getMagentoPrice() * 100
							),
							'track_inventory' => ( $item->getMagentoQty() === 'Not managed' ) ? false : true
						)
					)
				);

				//echo json_encode($request_body); exit;
				$response = Unirest\Request::post($connectHost . '/v1/' . $location_id . '/items', $requestHeaders, json_encode($request_body));
				//var_dump( $response ); exit;
				if ($response->code == 200) {
					$new_item = $response->body;
					if( $product->getTypeId() != 'configurable' ){
						if( $item->getMagentoQty() !== 'Not managed' ){
							$this->updateSquareQty( $new_item->getId(), $item->getMagentoQty() );
						}
					}
				} else {
					var_dump( $response ); exit;
				}

			} else { // Add as variation

				$configurable_collection_item = reset( $this->_collection->getItemsByColumnValue( 'entity_id', reset( $parent_ids ) ) );

				SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
				$api = new \SquareConnect\Api\CatalogApi();
				$catalog_object = $api->retrieveCatalogObject( $configurable_collection_item->getSquareId(), true )->getObject();
				$v1_id = reset( $catalog_object->getCatalogV1Ids() );
				$v1_id = $v1_id->getCatalogV1Id();

				$request_body = array(
					'name' => $item->getMagentoName(),
					'sku' => $item->getSku(),
					'pricing_type' => 'FIXED_PRICING',
					'price_money' => array(
						'currency_code' => 'USD',
						'amount' => $item->getMagentoPrice() * 100
					),
					'track_inventory' => ( $item->getMagentoQty() === 'Not managed' ) ? false : true
				);

				//echo json_encode($request_body); exit;
				//echo 'posting to: '. $connectHost . '/v1/' . $location_id . '/items/'. $v1_id .'/variations'; exit;
				$response = Unirest\Request::post($connectHost . '/v1/' . $location_id . '/items/'. $v1_id .'/variations', $requestHeaders, json_encode($request_body));
				//var_dump( $response ); exit;
				if ($response->code == 200) {
					$new_item = $response->body;
					if( $product->getTypeId() != 'configurable' ){
						if( $item->getMagentoQty() !== 'Not managed' ){
							$this->updateSquareQty( $new_item->getId(), $item->getMagentoQty() );
						}
					}
				} else {
					var_dump( $response ); exit;
				}

			}

		} else if( $product->getTypeId() == 'configurable' ){
			$request_body = array(
				'name' => $item->getMagentoName(),
				'variations' => array()
			);
			$child_ids = array_keys( reset( Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getId()) ) );
			foreach( $child_ids as $child_id ){
				$child_product = Mage::getModel('catalog/product')->load( $child_id );
				$child_collection_item = reset( $this->_collection->getItemsByColumnValue( 'entity_id', $child_id ) );
				$request_body['variations'][] = array(
					'name' => $child_product->getName(),
					'sku' => $child_product->getSku(),
					'pricing_type' => 'FIXED_PRICING',
					'price_money' => array(
						'currency_code' => 'USD',
						'amount' => $child_product->getPrice() * 100
					),
					'track_inventory' => ( $child_collection_item->getMagentoQty() === 'Not managed' ) ? false : true
				);
			}

			//echo json_encode($request_body); exit;
			$response = Unirest\Request::post($connectHost . '/v1/' . $location_id . '/items', $requestHeaders, json_encode($request_body));
			//var_dump( $response ); exit;
			if ($response->code == 200) {
				$new_item = $response->body;
				if( $product->getTypeId() != 'configurable' ){
					if( $item->getMagentoQty() !== 'Not managed' ){
						$this->updateSquareQty( $new_item->getId(), $item->getMagentoQty() );
					}
				}
			} else {
				var_dump( $response ); exit;
			}

		}

	}

	public function updateSquareManageStock( $item ){

		$location_id = Mage::helper('nvncbl_square')->getLocationId();
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();
		$square_id = $item->getSquareVariationId();

		SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
		$api = new \SquareConnect\Api\CatalogApi();

		$catalog_object = $api->retrieveCatalogObject( $square_id, true )->getObject();
		$catalog_item_variation = $catalog_object->getItemVariationData();
		$catalog_item_variation->setTrackInventory( $item->getMagentoQty() === 'Not managed' ? false : true );

		$location_overrides = $catalog_item_variation->getLocationOverrides();
		foreach( $location_overrides as $location_override ){
			$location_override->setTrackInventory( $item->getMagentoQty() === 'Not managed' ? false : true );
		}

		$catalog_object->setItemVariationData( $catalog_item_variation );

		$upsert_request = array(
		  "idempotency_key" => uniqid(),
		  "object" => $catalog_object
		);

		$apiResponse = $api->UpsertCatalogObject( $upsert_request );

	}

	public function updateMagentoManageStock( $item ){

		$product = Mage::getModel('catalog/product')->load( $item->getEntityId() );

		$stockItem = Mage::getModel('cataloginventory/stock_item');
		$stockItem->loadByProduct( $product->getId() );
		$stockItem->setUseConfigManageStock( false );

		if( !$stockItem->getId() ){
			$stockItem->setProductId( $product->getId() )->setStockId( 1 );
			if( $item->getQty() !== 'Not managed' ){
				$stockItem->setManageStock( true );
				//$stockItem->setQty( $item->getQty() );
				$stockItem->setIsInStock( $item->getQty() > 0 ? 1 : 0 );
			} else {
				$stockItem->setManageStock( false );
				$stockItem->setIsInStock( 1 );
			}
			$stockItem->save(); // need this line and the next line or else it won't work...
			$stockItem->loadByProduct( $product->getId() ); // ...kind of like it refreshes the object
		}

		if( $item->getQty() !== 'Not managed' ){
			$stockItem->setManageStock( true );
			//$stockItem->setQty( $item->getQty() );
			$stockItem->setIsInStock( $item->getQty() > 0 ? 1 : 0 );
		} else {
			$stockItem->setManageStock( false );
			$stockItem->setIsInStock( 1 );
		}

		$stockItem->save();

	}

	public function updateSquareQty( $variation_id, $qty_delta ){

		$location_id = Mage::helper('nvncbl_square')->getLocationId();
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();

		SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
		$api = new \SquareConnect\Api\CatalogApi();
		$catalog_object = $api->retrieveCatalogObject( $variation_id, true )->getObject();
		$catalog_item_variation = $catalog_object->getItemVariationData();
		$v1_id = reset( $catalog_object->getCatalogV1Ids() );
		$v1_id = $v1_id->getCatalogV1Id();

		$connectHost = 'https://connect.squareup.com';

		$requestHeaders = array (
			'Authorization' => 'Bearer ' . $access_token,
			'Accept' => 'application/json',
			'Content-Type' => 'application/json'
		);

		$request_body = array(
			'quantity_delta' => $qty_delta,
			'adjustment_type' => 'MANUAL_ADJUST',
			'memo' => 'Synchronize from Magento'
		);
		//$response = Unirest\Request::post($connectHost . '/v1/' . $location_id . '/inventory/'. $variation_id .'', $requestHeaders, json_encode($request_body));
		$response = Unirest\Request::post($connectHost . '/v1/' . $location_id . '/inventory/'. $v1_id .'', $requestHeaders, json_encode($request_body));
		if ($response->code != 200) {
			var_dump( $response->body );
			exit;
		}

	}

	public function updateMagentoQty( $item ){

		$product = Mage::getModel('catalog/product')->load( $item->getEntityId() );

		$stockItem = Mage::getModel('cataloginventory/stock_item');
		$stockItem->loadByProduct( $product->getId() );

		if( !$stockItem->getId() ){
			$stockItem->setProductId( $product->getId() )->setStockId( 1 );
			if( $item->getQty() !== 'Not managed' ){
				$stockItem->setQty( $item->getQty() );
				$stockItem->setIsInStock( $item->getQty() > 0 ? 1 : 0 );
			} else {
				$stockItem->setIsInStock( 1 );
			}
			$stockItem->save(); // need this line and the next line or else it won't work...
			$stockItem->loadByProduct( $product->getId() ); // ... kind of like it refreshes the object
		}

		if( $item->getQty() !== 'Not managed' ){
			$stockItem->setQty( $item->getQty() );
			$stockItem->setIsInStock( $item->getQty() > 0 ? 1 : 0 );
		} else {
			$stockItem->setIsInStock( 1 );
		}

		$stockItem->save();

	}

	public function updateSquarePrice( $item ){

		$location_id = Mage::helper('nvncbl_square')->getLocationId();
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();
		$square_id = $item->getSquareVariationId();
//$square_id = 'JCI7RGJK7HNS4H4J37INDO3D';
		SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
		$api = new \SquareConnect\Api\CatalogApi();

		$catalog_object = $api->retrieveCatalogObject( $square_id, true )->getObject();
//		var_dump( $catalog_object ); exit;
		$catalog_item_variation = $catalog_object->getItemVariationData();
		$price_money = $catalog_item_variation->getPriceMoney();
		if( $price_money ){
			$price_money->setAmount( (int) ( $item->getMagentoPrice() * 100 ) );
			$catalog_item_variation->setPriceMoney( $price_money );
			$catalog_object->setItemVariationData( $catalog_item_variation );
		} else {

//			$location_overrides = $catalog_item_variation->getLocationOverrides();
//			foreach( $location_overrides as $location_override ){
//				$location_override->setPricingType( 'FIXED_PRICING' );
//				$location_override->setPriceMoney( array(
//					'currency_code' => 'USD',
//					'amount' => $item->getMagentoPrice() * 100
//				) );
				/*$price_money = $location_override->getPriceMoney();*/
				$catalog_item_variation->setPricingType( 'FIXED_PRICING' );
				$catalog_item_variation->setPriceMoney( array(
					'currency' => 'USD',
					'amount' => $item->getMagentoPrice() * 100
				) );
//			}
		}

		$upsert_request = array(
		  "idempotency_key" => uniqid(),
		  "object" => $catalog_object
		);

//		echo $square_id; exit;
//		var_dump( $catalog_object ); exit;

		$apiResponse = $api->UpsertCatalogObject( $upsert_request );

	}

	public function updateMagentoPrice( $item ){
		$product = Mage::getModel('catalog/product')->load( $item->getEntityId() );
		$product->setPrice( $item->getPrice() );
		$product->save();
	}

	public function updateSquareName( $item ){

		$location_id = Mage::helper('nvncbl_square')->getLocationId();
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();
		SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
		$api = new \SquareConnect\Api\CatalogApi();

		/* Item */
		$square_id = $item->getSquareId();

		$catalog_object = $api->retrieveCatalogObject( $square_id, true )->getObject();
		$catalog_item = $catalog_object->getItemData();
		$catalog_item->setName( $item->getMagentoName() );
		//$catalog_item->setImageUrl( 'https://nvncbl.com/media/catalog/product/m/a/mars-planet-water-nasa.jpg' );

		$upsert_request = array(
		  "idempotency_key" => uniqid(),
		  "object" => $catalog_object
		);

		$apiResponse = $api->UpsertCatalogObject( $upsert_request );

		/* Item Variation */
		$square_id = $item->getSquareVariationId();

		$catalog_object = $api->retrieveCatalogObject( $square_id, true )->getObject();
		$catalog_item_variation = $catalog_object->getItemVariationData();
		$catalog_item_variation->setName( $item->getMagentoName() );
		$catalog_object->setItemVariationData( $catalog_item_variation );

		$upsert_request = array(
		  "idempotency_key" => uniqid(),
		  "object" => $catalog_object
		);

		$apiResponse = $api->UpsertCatalogObject( $upsert_request );

	}

	public function updateMagentoName( $item ){
		$product = Mage::getModel('catalog/product')->load( $item->getEntityId() );
		$product->setName( $item->getName() );
		$product->save();
	}

	public function updateSquareDescription( $item ){

		$location_id = Mage::helper('nvncbl_square')->getLocationId();
		$access_token = Mage::helper('nvncbl_square')->getAccessToken();
		SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
		$api = new \SquareConnect\Api\CatalogApi();

		/* Item */
		$square_id = $item->getSquareId();

		$catalog_object = $api->retrieveCatalogObject( $square_id, true )->getObject();
		$catalog_item = $catalog_object->getItemData();
		$catalog_item->setDescription( $item->getMagentoDescription() );

		$upsert_request = array(
		  "idempotency_key" => uniqid(),
		  "object" => $catalog_object
		);

		$apiResponse = $api->UpsertCatalogObject( $upsert_request );

	}

	public function updateMagentoDescription( $item ){
		$product = Mage::getModel('catalog/product')->load( $item->getEntityId() );
		$product->setDescription( $item->getDescription() );
		$product->save();
	}

}