<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('aligent_emarsys/remoteSystemSyncFlags');
$table = $installer->getConnection();
// Still not making the indices unique as we need to have multiple customer_entity_id of zero
$table->addIndex(
    $tableName,
    $installer->getIdxName($tableName, array('harmony_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
    array('harmony_id'),
    array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
);

$table->addIndex(
    $tableName,
    $installer->getIdxName($tableName, array('emarsys_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
    array('emarsys_id'),
    array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
);


$installer->endSetup();

