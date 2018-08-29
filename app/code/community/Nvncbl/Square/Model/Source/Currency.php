<?php

require_once 'Nvncbl/Square/autoload.php';

class Nvncbl_Square_Model_Source_Currency
{
	public function toOptionArray(){

		$output = array(
			array(
				'value' => '',
				'label' => ' -- None Selected -- '
			)
		);

		foreach( Mage::helper('nvncbl_square')->getSupportedCurrencies() as $supported_currency ){
			$output[] = array(
				'value' => $supported_currency[ 'code' ],
				'label' => $supported_currency[ 'code' ] .' - '. $supported_currency[ 'label' ]
			);
		}

		return $output;
	}
}
