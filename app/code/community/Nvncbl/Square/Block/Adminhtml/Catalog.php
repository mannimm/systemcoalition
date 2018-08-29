<?php

class Nvncbl_Square_Block_Adminhtml_Catalog extends Mage_Adminhtml_Block_Widget_Container {

	public function __construct(){
		parent::__construct();
		$this->setTemplate('nvncbl_square/catalog.phtml');
	}

	protected function _prepareLayout(){

		$this->setChild('grid', $this->getLayout()->createBlock('nvncbl_square/adminhtml_catalog_grid', 'catalog.grid'));

		return parent::_prepareLayout();
	}

	public function getGridHtml(){
		return $this->getChildHtml('grid');
	}

}
