<?php

class Nvncbl_Square_Model_Source_ExpiredAuthorizations
{
	public function toOptionArray()
	{
		return array(
			array(
				'value' => 0,
				'label' => Mage::helper('nvncbl_square')->__('Warn admin and don\'t capture')
			),
			array(
				'value' => 1,
				'label' => Mage::helper('nvncbl_square')->__('Try to re-create the charge with a saved card')
			),
		);
	}
}
