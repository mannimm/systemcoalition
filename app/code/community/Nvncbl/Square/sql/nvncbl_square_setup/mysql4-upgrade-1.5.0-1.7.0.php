<?php

$installer = $this;

$installer->startSetup();

try {
	$installer->run("
	alter table nvncbl_squaresubscriptions_customers add column customer_email varchar(255) null
	");
}
catch (Exception $e) {}

$installer->endSetup();