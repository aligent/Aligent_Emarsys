<?php

$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('aligent_emarsys/remoteSystemSyncFlags');
// Check if the table already exists
if ($installer->getConnection()->isTableExists($tableName)) {
    // Remove any of the duplicate records that are going to break the scripts
    $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection();
    $items->removeAllFieldsFromSelect();
    $items->getSelect()->columns('max(id) as mId')->group('customer_entity_id')->having('count(id) > 1')->where('customer_entity_id > 0');

    $ids = [];
    foreach($items as $item){
        $ids[] = $item->getData('mId');
    }

    $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection()->addFieldToFilter('id', ['in'=>$ids]);
    foreach($items as $item){
        $item->delete();
    }
    $table = $installer->getConnection();

    // Still not making the indices unique as we need to have multiple customer_entity_id of zero
    $table->addIndex(
        $installer->getIdxName(
            'aligent_emarsys/remoteSystemSyncFlags',
            array('customer_entity_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
        ),
        array( 'customer_entity_id' ),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
    );

    $table->addIndex(
        $installer->getIdxName(
            'aligent_emarsys/remoteSystemSyncFlags',
            array('newsletter_subscriber_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
        ),
        array( 'customer_entity_id' ),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
    );

    $table->addIndex(
        $installer->getIdxName(
            'aligent_emarsys/remoteSystemSyncFlags',
            array('email'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
        ),
        array( 'email' ),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
    );
}
$installer->endSetup();
