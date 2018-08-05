<?php

class Nvncbl_MenuBuilderPro_Block_Adminhtml_Menu_Edit extends Mage_Core_Block_Template {

	public function getMenu(){

		$menu_id = Mage::app()->getRequest()->getParam('menu_id');

		$menu = Mage::getModel('menubuilderpro/menu');
		if( $menu_id ){
			$menu->load( $menu_id );
		}

		return $menu;

	}

	public function getBackButtonHtml(){
		return $this->getLayout()->createBlock('adminhtml/widget_button')
			->setData(array(
				'label'     => Mage::helper('menubuilderpro')->__('Back to Menus'),
				'onclick'   => 'setLocation(\''. Mage::helper('adminhtml')->getUrl('*/*/', array()).'\')',
				'class' => 'back'
			))->toHtml();
	}

	public function getSaveButtonHtml(){
		return $this->getLayout()->createBlock('adminhtml/widget_button')
			->setData(array(
				'label'     => Mage::helper('menubuilderpro')->__('Save Menu'),
				'onclick'   => 'saveMenu()',
				'class' => 'save'
			))->toHtml();
	}

	public function getDeleteButtonHtml(){
		return $this->getLayout()->createBlock('adminhtml/widget_button')
			->setData(array(
				'label'     => Mage::helper('menubuilderpro')->__('Delete Menu'),
				'onclick'   => 'if( confirm(\'Are you sure you want to delete this menu?\') ){ setLocation(\''. Mage::helper('adminhtml')->getUrl('*/*/delete', array( 'menu_id' => $this->getMenu()->getId() )).'\'); }',
				'class' => 'delete'
			))->toHtml();
	}

}