<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('aligent_emarsys/remoteSystemSyncFlags');
$connection = $installer->getConnection();

$connection->addColumn($tableName, 'country', Varien_Db_Ddl_Table::TYPE_VARCHAR . '(255)');

$installer->endSetup();
