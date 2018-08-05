<?php

class Nvncbl_Square_SavedcardsController extends Mage_Core_Controller_Front_Action
{
	protected function _getSession()
	{
		return Mage::getSingleton('customer/session');
	}

	public function preDispatch()
	{
		parent::preDispatch();
		if (!Mage::getSingleton('customer/session')->authenticate($this)) {
			$this->setFlag('', 'no-dispatch', true);
		}
	}

	public function indexAction()
	{
		$square = Mage::getModel('nvncbl_square/standard');

		$deleteCards = $this->getRequest()->getParam('card', null);
		if (!empty($deleteCards))
		{
			$square->deleteCards($deleteCards);
			$this->_redirect('customer/savedcards');
		}

		$newcard = $this->getRequest()->getParam('newcard', null);
		if (!empty($newcard))
		{
			if ($newcard)
			{
				if (isset($newcard['cc_squarejs_token']))
				{
					// This case is when AVS is enabled
					if (strpos($newcard['cc_squarejs_token'], ':'))
					{
						$card = explode(':', $newcard['cc_squarejs_token']);
						$params = $card[0];
					}
					else
						$params = $newcard['cc_squarejs_token'];
				}
				else
					$params = array(
						"name" => $newcard['cc_owner'],
						"number" => $newcard['cc_number'],
						"cvc" => $newcard['cc_cid'],
						"exp_month" => $newcard['cc_exp_month'],
						"exp_year" => $newcard['cc_exp_year']
					);

				try
				{
					$square->addCardToCustomer($params);
					$this->_redirect('customer/savedcards');
				}
				catch (Square_Error $e)
				{
					Mage::getSingleton('core/session')->addError($e->getMessage());
				}
				catch (Exception $e)
				{
					Mage::log($e->getMessage());
					Mage::getSingleton('core/session')->addError("Sorry, the card could not be added!");
				}
			}
		}

		$this->loadLayout();
		$this->renderLayout();
	}
}