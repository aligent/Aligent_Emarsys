<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('aligent_emarsys/remoteSystemSyncFlags');
$table = $installer->getConnection();

$table->modifyColumn($tableName, 'harmony_id', Varien_Db_Ddl_Table::TYPE_VARCHAR . "(12) NULL");

$installer->endSetup();

