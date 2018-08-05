<?php

class Nvncbl_Square_Model_Source_Mode
{
	public function toOptionArray()
	{
		return array(
			array(
				'value' => Nvncbl_Square_Model_Standard::TEST,
				'label' => Mage::helper('nvncbl_square')->__('Test')
			),
			array(
				'value' => Nvncbl_Square_Model_Standard::LIVE,
				'label' => Mage::helper('nvncbl_square')->__('Live')
			),
		);
	}
}
