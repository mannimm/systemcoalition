<?php

$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS {$this->getTable('nvncbl_menubuilderpro_menu')};
CREATE TABLE {$this->getTable('nvncbl_menubuilderpro_menu')} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `tree` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
");

$installer->endSetup();

?>