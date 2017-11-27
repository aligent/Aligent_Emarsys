<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('aligent_emarsys/remoteSystemSyncFlags');
// Check if the table already exists
if ($installer->getConnection()->isTableExists($tableName)) {
    // Remove any of the duplicate records that are going to break the scripts
    $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection();
    $items->removeAllFieldsFromSelect();
    $items->getSelect()->columns('max(id) as mId')->group('customer_entity_id')->having('count(id) > 1')->where('customer_entity_id > 0');
    Mage::log('Remove customers with SQL: ' . $items->getSelectSql());

    $ids = [];
    foreach($items as $item){
        $ids[] = $item->getData('mId');
    }

    $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection();
    $items->removeAllFieldsFromSelect();
    $items->getSelect()->columns('max(id) as mId')->group('newsletter_subscriber_id')->having('count(id) > 1')->where('newsletter_subscriber_id > 0')->where('customer_entity_id=0');
    Mage::log('Remove newsletters with SQL: ' . $items->getSelectSql());
    foreach($items as $item){
        $ids[] = $item->getData('mId');
    }

    $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection()->addFieldToFilter('id', ['in'=>$ids]);
    foreach($items as $item){
        $item->delete();
    }

    try {
        $table = $installer->getConnection();
        // Still not making the indices unique as we need to have multiple customer_entity_id of zero
        $table->addIndex(
            $tableName,
            $installer->getIdxName($tableName, array('customer_entity_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
            array('customer_entity_id'),
            array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
        );

        $table->addIndex(
            $tableName,
            $installer->getIdxName($tableName, array('newsletter_subscriber_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
            array('newsletter_subscriber_id'),
            array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
        );

        $table->addIndex(
            $tableName,
            $installer->getIdxName($tableName, array('email'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
            array('email'),
            array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
        );

        $table->addColumn($tableName, "created_at", array(
            'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            "nullable"=>false,
            "default" => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
            "comment" => "Created at"
        ));

        $table->addColumn($tableName, "updated_at", array(
            'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            "nullable"=>true,
            "comment" => "Updated at"
        ));
    }catch(\Exception $e){
        Mage::log("An exception: " . $e->getMessage());
    }
}
$installer->endSetup();
