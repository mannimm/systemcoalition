<?php

class Nvncbl_MenuBuilderPro_Block_Standalone extends Nvncbl_MenuBuilderPro_Block_Page_Html_Topmenu {

	public function _construct(){
		parent::_construct();
		$this->setTemplate( 'nvncbl_menubuilderpro/standalone.phtml' );
	}

	public function getHtml($outermostClass = '', $childrenWrapClass = ''){
		Mage::dispatchEvent('page_block_html_topmenu_gethtml_before', array(
			'menu' => $this->_menu
		));

		$this->_menu->setOutermostClass($outermostClass);
		$this->_menu->setChildrenWrapClass($childrenWrapClass);

		/* This is where we set the menu ID from the layout XML */
		Mage::getModel('menubuilderpro/observer')->setMenu( $this->_menu, $this->getMenuId() );
		/* END This is where we set the menu ID from the layout XML */

		$html = $this->_getHtml($this->_menu, $childrenWrapClass);

		Mage::dispatchEvent('page_block_html_topmenu_gethtml_after', array(
			'menu' => $this->_menu,
			'html' => $html
		));

		return $html;
	}

}