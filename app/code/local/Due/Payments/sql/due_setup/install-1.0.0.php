<?php

$installer = $this;
$this->startSetup();

// Install Card table
$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('duecard')}` (
  `card_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Card ID',
  `customer_id` int(10) NOT NULL COMMENT 'Customer Id',
  `token` varchar(255) NOT NULL COMMENT 'Token',
  `type` varchar(255) NOT NULL COMMENT 'Card Type',
  `last4` varchar(255) NOT NULL COMMENT 'Card Last4',
  `exp_month` varchar(255) NOT NULL,
  `exp_year` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL COMMENT 'Date',
  PRIMARY KEY (`card_id`),
  UNIQUE KEY `token` (`token`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Saved Cards of Due';
");

$this->endSetup();
