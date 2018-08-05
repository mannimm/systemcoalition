<?php

class Nvncbl_MenuBuilderPro_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getTree( $menu_id = null ){

		$menu = Mage::getModel('menubuilderpro/menu');
		if( $menu_id ){
			$menu->load( $menu_id );
		}

		$tree = json_decode( $menu->getTree(), true );

		return $tree;

	}

}