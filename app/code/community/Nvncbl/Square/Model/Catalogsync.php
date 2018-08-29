<?php

require_once 'Nvncbl/Square/autoload.php';

class Nvncbl_Square_Model_Catalogsync extends Varien_Object {

	protected $_changes = array();

	public function identifyChanges(){

		$catalog_sync_settings = new Varien_Object( Mage::helper('nvncbl_square')->getCatalogSyncSettings() );

		/* Load Magento SKUs */
		$products = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect( 'sku' )
			->addAttributeToFilter( 'entity_id', array( 'nin' => array( $matches_magento_ids ) ) )
			->addAttributeToFilter( 'type_id', array( 'nin' => array( 'configurable', 'grouped', 'bundle' ) ) )
			;
		$magento_skus = $products->getColumn( 'sku' );

		if( $catalog_sync_settings->getUpsertSquare() == '1' ){
			$this->_changes['additions_to_square'] = $this->_identifyAdditionsToSquare();
		}
		if( $catalog_sync_settings->getUpsertMagento() == '1' ){
			$this->_changes['additions_to_magento'] = $this->_identifyAdditionsToMagento();
		}

		return $this;
	}

	public function makeChanges(){

		/* Process upserts before synchronizing attributes */

		/* Identify matching products */

		/* Process attribute synchronization */

		return $this;
	}

}