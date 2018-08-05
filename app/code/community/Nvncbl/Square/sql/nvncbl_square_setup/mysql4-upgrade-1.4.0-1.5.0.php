<?php

$installer = $this;

$installer->startSetup();

if (!$installer->tableExists('nvncbl_squaresubscriptions_customers')) {

	$installer->run("

	CREATE TABLE nvncbl_squaresubscriptions_customers (
	  `id` int(11) unsigned NOT NULL auto_increment,
	  `customer_id` int(11) unsigned NOT NULL,
	  `square_id` varchar(255) NOT NULL,
	  `last_retrieved` int NOT NULL DEFAULT 0,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	");

}

$installer->endSetup();