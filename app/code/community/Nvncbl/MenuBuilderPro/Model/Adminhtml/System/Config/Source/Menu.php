<?php

class Nvncbl_MenuBuilderPro_Model_Adminhtml_System_Config_Source_Menu {

	public function toOptionArray(){
		$options = array();

		/* Empty value */
		$options[] = array( 'value' => '', 'label' => 'Select a Menu' );

		/* Add the available menus */
		$available_menus = Mage::getResourceModel('menubuilderpro/menu_collection');
		foreach( $available_menus as $available_menu ){
			$options[] = array(
				'value' => $available_menu->getId(),
				'label' => $available_menu->getLabel()
			);
		}

		return $options;
	}

}