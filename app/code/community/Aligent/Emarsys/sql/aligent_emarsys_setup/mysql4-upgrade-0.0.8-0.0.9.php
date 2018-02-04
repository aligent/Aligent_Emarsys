<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$aligentEmarsysTable = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getResource()->getMainTable();

$tableName = $installer->getTable('ae_newsletters');
$table = $installer->getConnection()->newTable($tableName);
$table->addColumn('ae_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array( 'unsigned'  => true, 'nullable'  => false));
$table->addColumn('subscriber_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array( 'unsigned'  => true, 'nullable'  => false));
$installer->getConnection()->createTable($table);
$table->addIndex(
    $tableName,
    $installer->getIdxName($tableName, array('ae_id','subscriber_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_PRIMARY ),
    array('ae_id','subscriber_id'),
    array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_PRIMARY)
);

// Create link records for all existing sync flags
$syncs = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection();

$lwHelper = Mage::helper('aligent_emarsys/lightweightDataHelper');
$writer = $lwHelper->getWriter();


Mage::register('emarsys_newsletter_ignore', true);
foreach($syncs as $sync){
    if(!$sync->getNewsletterSubscriberId()){
        $customer = Mage::getModel('customer/customer')->load($sync->getCustomerEntityId());
        $newSub = Mage::helper('aligent_emarsys')->createSubscription($customer);
    }elseif($sync->getCustomerEntityId()){
        $newSub = Mage::getModel('newsletter/subscriber')->load($sync->getNewsletterSubscriberId());
        if($newSub->getCustomerId() != $sync->getCustomerEntityId()){
            $newSub->setCustomerId($sync->getCustomerEntityId());
            $newSub->save();
        }
    }

    $writer->insert($tableName, array(
        'ae_id' => $sync->getId(),
        'subscriber_id' => $newSub->getId()
    ));
}
Mage::unregister('emarsys_newsletter_ignore');

$installer->getConnection()->dropColumn($aligentEmarsysTable, 'customer_entity_id');
$installer->getConnection()->dropColumn($aligentEmarsysTable, 'newsletter_subscriber_id');

$installer->endSetup();
