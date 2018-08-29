<?php

class Nvncbl_Square_Model_Source_PaymentAction
{
	public function toOptionArray()
	{
		return array(
			array(
				'value' => 'tokenize',
				'label' => Mage::helper('nvncbl_square')->__('Tokenize (Authorize Later, Capture Later)')
			),
			array(
				'value' => Mage_Paygate_Model_Authorizenet::ACTION_AUTHORIZE,
				'label' => Mage::helper('nvncbl_square')->__('Authorize Only')
			),
			array(
				'value' => Mage_Paygate_Model_Authorizenet::ACTION_AUTHORIZE_CAPTURE,
				'label' => Mage::helper('nvncbl_square')->__('Authorize and Capture')
			),
		);
	}
}
