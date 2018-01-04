<?php

$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('aligent_emarsys/remoteSystemSyncFlags');
// Check if the table already exists
if ($installer->getConnection()->isTableExists($tableName)) {
    $table = $installer->getConnection();

    // Still not making the indices unique as we need to have multiple customer_entity_id of zero
    $table->dropIndex( $tableName, $installer->getIdxName(
        'aligent_emarsys/remoteSystemSyncFlags',
        array('newsletter_subscriber_id'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
    ) );

    $table->addIndex(
        $tableName,
        $installer->getIdxName(
            'aligent_emarsys/remoteSystemSyncFlags',
            array('newsletter_subscriber_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
        ),
        array( 'newsletter_subscriber_id' ),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
    );

    $table->dropIndex( $tableName, $installer->getIdxName(
        'aligent_emarsys/remoteSystemSyncFlags',
        array('customer_entity_id'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
    ) );

    $table->addIndex(
        $tableName,
        $installer->getIdxName(
            'aligent_emarsys/remoteSystemSyncFlags',
            array('customer_entity_id'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
        ),
        array( 'customer_entity_id' ),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX)
    );
}
$installer->endSetup();
