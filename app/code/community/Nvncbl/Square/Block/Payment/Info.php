<?php

class Nvncbl_Square_Block_Payment_Info extends Mage_Payment_Block_Info
{
	protected function _construct()
	{
		parent::_construct();
		if (Mage::app()->getStore()->isAdmin())
			$this->setTemplate('nvncbl_square/payment/info/default.phtml');
	}
}