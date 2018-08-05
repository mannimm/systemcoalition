<?php

class Nvncbl_MenuBuilderPro_Block_Adminhtml_Menu_Edit_Tree extends Mage_Core_Block_Template {

	public function getTree(){

		$menu_id = Mage::app()->getRequest()->getParam('menu_id');

		return Mage::helper('menubuilderpro')->getTree( $menu_id );

	}

}