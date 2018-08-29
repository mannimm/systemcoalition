<?php

class Nvncbl_Square_Adminhtml_SquareController extends Mage_Adminhtml_Controller_Action {

	public function catalogAction(){
		$this->loadLayout()->_setActiveMenu('nvncbl_square/catalog');
		$this->renderLayout();
	}

	public function catalogSaveSettingsAction(){

		$catalog_sync_settings = $this->getRequest()->getParam('catalog_sync_settings');

		Mage::getConfig()->saveConfig( 'payment/nvncbl_square/catalog_sync_settings', json_encode( $catalog_sync_settings ) );

		Mage::app()->getStore()->resetConfig();

		$this->_redirect('*/*/catalog');

	}

	public function catalogSyncNowAction(){

		try {
			$syncer = Mage::getModel('nvncbl_square/syncer');
			$syncer->getCollection();
			$syncer->getCollection( true );
			$this->_redirect('*/*/catalog');
		} catch( Exception $e ){
			echo $e->getMessage();
			exit;
		}

	}

}
