<?php

$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()->newTable($installer->getTable('aligent_emarsys_remote_system_sync_flags'));

$table->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'identity'  => true,
    'unsigned'  => true,
    'nullable'  => false,
    'primary'   => true,), 'Id');

$table->addColumn('customer_entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array( 'nullable'  => true , 'unsigned'  => true), 'Customer Id');
$table->addColumn('newsletter_subscriber_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array( 'nullable'  => true, 'unsigned'=>true), 'Newsletter Subscriber Id');
$table->addColumn('emarsys_sync_dirty', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array( 'nullable'  => true ), 'Required Emarsys Sync');
$table->addColumn('harmony_sync_dirty', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array( 'nullable'  => true ), 'Required Harmony Sync');
$table->addColumn('emarsys_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array( 'unsigned'  => true, 'nullable'  => true ), 'Emarsys Id');
$table->addColumn('harmony_id', Varien_Db_Ddl_Table::TYPE_VARCHAR , 12, array( 'nullable'=>true), 'Harmony Id');
$table->addColumn('first_name', Varien_Db_Ddl_Table::TYPE_VARCHAR , 120, array( 'nullable'=>true), 'First Name');
$table->addColumn('last_name', Varien_Db_Ddl_Table::TYPE_VARCHAR , 120, array( 'nullable'=>true), 'Last Name');
$table->addColumn('email', Varien_Db_Ddl_Table::TYPE_VARCHAR , 120, array( 'nullable'=>true), 'Email');
$table->addColumn('gender', Varien_Db_Ddl_Table::TYPE_VARCHAR , 20, array( 'nullable'=>true), 'Gender');
$table->addColumn('dob', Varien_Db_Ddl_Table::TYPE_DATE, 12, array( 'nullable'=>true), 'Date of Birth');

$installer->getConnection()->createTable($table);

$installer->endSetup();
