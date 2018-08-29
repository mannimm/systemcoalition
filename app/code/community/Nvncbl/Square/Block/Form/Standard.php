<?php

class Nvncbl_Square_Block_Form_Standard extends Mage_Payment_Block_Form_Cc
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('nvncbl_square/form/standard.phtml');
	}

}
