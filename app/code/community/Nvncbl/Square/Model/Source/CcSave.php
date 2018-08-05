<?php

class Nvncbl_Square_Model_Source_CcSave
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
				'label' => Mage::helper('nvncbl_square')->__('Ask the customer')
			),
			array(
				'value' => 2,
				'label' => Mage::helper('nvncbl_square')->__('Save without asking')
			),
		);
	}
}
