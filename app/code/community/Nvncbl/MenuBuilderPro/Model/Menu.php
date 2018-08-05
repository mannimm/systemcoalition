<?php

class Nvncbl_MenuBuilderPro_Model_Menu extends Mage_Core_Model_Abstract {

	public function _construct(){
		parent::_construct();
		$this->_init('menubuilderpro/menu');
	}

}