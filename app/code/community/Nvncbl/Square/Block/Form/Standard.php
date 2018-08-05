<?php

class Nvncbl_Square_Block_Form_Standard extends Mage_Payment_Block_Form_Cc
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('nvncbl/square/form/standard.phtml');

		// We need to check if Square Subscriptions is installed, this is the
		// cross-magento version compatible way.
		$path = dirname(__FILE__).DS.'..'.DS.'..'.DS.'Model'.DS.'Nvncbl_SquareSubscriptions.xml';
		if (file_exists($path))
			$this->square = Mage::getModel('nvncbl_square/subscriptions');
		else
			$this->square = Mage::getModel('nvncbl_square/standard');

		$this->cardAutoDetect = $this->square->store->getConfig('payment/nvncbl_square/card_autodetect');
	}

	public function autoDetectCard()
	{
		return $this->cardAutoDetect && $this->cardAutoDetect > 0;
	}

	public function showAcceptedCardTypes()
	{
		return $this->cardAutoDetect == 1;
	}

	public function getOnCardNumberChangedAnimation()
	{
		switch ($this->cardAutoDetect)
		{
			case 1: return 'onCardNumberChangedFade';
			case 2: return 'onCardNumberChangedSlide';
			default: return '';
		}
	}

	public function getOnKeyUpCardNumber()
	{
		if ($this->autoDetectCard())
		{
			$callback = $this->getOnCardNumberChangedAnimation();
			return "onkeyup=\"$callback(this)\"";
		}

		return '';
	}

	public function getAcceptedCardTypes()
	{
		$types = Mage::getConfig()->getNode('global/payment/nvncbl_square/cc_types')->asArray();
		$acceptedTypes = $this->square->store->getConfig('payment/nvncbl_square/cctypes');

		uasort($types, array('Mage_Payment_Model_Config', 'compareCcTypes'));

		foreach ($types as $data)
		{
			if (empty($acceptedTypes)) // Slide animation, returns all possible types
			{
				$cardTypes[$data['code']] = $data['name'];
			}
			else if (isset($data['code']) && isset($data['name']) && strstr($acceptedTypes, $data['code'])) // Fade animation, takes into account selected types
			{
				$cardTypes[$data['code']] = $data['name'];
			}
		}

		return $cardTypes;
	}
}
