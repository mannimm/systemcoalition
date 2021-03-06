<?php

class Nvncbl_Square_Model_Source_CcType
{
	public function toOptionArray()
	{
		$options =  array();

		$_types = Mage::getConfig()->getNode('global/payment/nvncbl_square/cc_types')->asArray();

		uasort($_types, array('Mage_Payment_Model_Config', 'compareCcTypes'));

		foreach ($_types as $data)
		{
			if (isset($data['code']) && isset($data['name']))
			{
				$options[] = array(
				   'value' => $data['code'],
				   'label' => $data['name']
				);
			}
		}

		return $options;
	}
}
