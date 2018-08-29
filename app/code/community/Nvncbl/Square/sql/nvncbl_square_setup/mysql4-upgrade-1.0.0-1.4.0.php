<?php

$installer = $this;

$attribute  = array(
	'type' => 'varchar',
	'input' => 'text',
	'label' => 'Square Payments Customer ID',
	'global' => 1,
	'visible' => 1,
	'default' => '0',
	'required' => 0,
	'user_defined' => 0,
	'comment' => 'Internally used by Square Payments extension to contain the record ID that Square uses for a cutsomer',
);

$installer->addAttribute('customer', 'square_customer_id', $attribute);

$installer->endSetup();