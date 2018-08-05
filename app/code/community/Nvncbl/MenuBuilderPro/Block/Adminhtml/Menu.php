<?php

class Nvncbl_MenuBuilderPro_Block_Adminhtml_Menu extends Mage_Adminhtml_Block_Widget_Container {

	public function __construct(){
		parent::__construct();
		$this->setTemplate('nvncbl_menubuilderpro/menu.phtml');
	}

	protected function _prepareLayout(){
		$this->_addButton('add_new', array(
			'label'   => Mage::helper('catalog')->__('Add Menu'),
			'onclick' => "setLocation('{$this->getUrl('*/*/edit')}')",
			'class'   => 'add'
		));

		$this->setChild('grid', $this->getLayout()->createBlock('menubuilderpro/adminhtml_menu_grid', 'menu.grid'));

		return parent::_prepareLayout();
	}

	public function getAddNewButtonHtml(){
		return $this->getChildHtml('add_new_button');
	}

	public function getGridHtml(){
		return $this->getChildHtml('grid');
	}

}
