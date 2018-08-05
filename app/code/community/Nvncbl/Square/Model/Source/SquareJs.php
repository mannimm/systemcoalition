<?php

class Nvncbl_Square_Model_Source_SquareJs
{
	public function toOptionArray()
	{
		return array(
			array(
				'value' => false,
				'label' => Mage::helper('nvncbl_square')->__('Disabled')
			),
			array(
				'value' => true,
				'label' => Mage::helper('nvncbl_square')->__('Enabled')
			),
		);
	}
}
