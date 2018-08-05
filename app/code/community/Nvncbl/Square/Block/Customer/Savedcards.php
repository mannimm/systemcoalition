<?php

class Nvncbl_Square_Block_Customer_Savedcards extends Mage_Customer_Block_Account_Dashboard
{
	protected function _construct()
	{
		parent::_construct();
		$this->square = Mage::getModel('nvncbl_square/standard');
		$this->form = Mage::app()->getLayout()->createBlock('payment/form_cc');
	}

	public function getCcMonths()
	{
		return $this->form->getCcMonths();
	}

	public function getParam($str)
	{
		$newcard = $this->getRequest()->getParam('newcard', null);
		if (empty($newcard) || empty($newcard[$str])) return null;

		return $newcard[$str];
	}

	public function getCcYears()
	{
		return $this->form->getCcYears();
	}
}
