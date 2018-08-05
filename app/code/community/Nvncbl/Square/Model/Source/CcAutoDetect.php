<?php

class Nvncbl_Square_Model_Source_CcAutoDetect
{
	public function toOptionArray()
	{
		return array(
			array(
				'value' => false,
				'label' => Mage::helper('nvncbl_square')->__('Disabled')
			),
			array(
				'value' => 1,
				'label' => Mage::helper('nvncbl_square')->__('Show all accepted card types')
			),
			array(
				'value' => 2,
				'label' => Mage::helper('nvncbl_square')->__('Show only the detected card type')
			),
		);
	}
}
