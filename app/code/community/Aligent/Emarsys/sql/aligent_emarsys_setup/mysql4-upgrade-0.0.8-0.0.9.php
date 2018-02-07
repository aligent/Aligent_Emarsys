<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$connection = $installer->getConnection();

$aligentEmarsysTable = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getResource()->getMainTable();
$tableName = $installer->getTable('ae_newsletters');

if (!$connection->isTableExists($tableName)) {
    $table = $connection->newTable($tableName);
    $table->addColumn('ae_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => false));
    $table->addColumn('subscriber_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => false));
    $connection->createTable($table);

    createIndex($installer, $tableName, array('subscriber_id'), false, true);
    createIndex($installer, $tableName, array('ae_id','subscriber_id'), true, true);
}

$syncTable = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getResource()->getMainTable();
$customerTable = Mage::getModel('customer/customer')->getResource()->getEntityTable();
$newsletterTable = Mage::getModel('newsletter/subscriber')->getResource()->getMainTable();

createIndex($installer,$syncTable, array('email'), false, false);
createIndex($installer,$newsletterTable, array('subscriber_email'), false, false);

echo "done";


$lwHelper = Mage::helper('aligent_emarsys/lightweightDataHelper');
$writer = $lwHelper->getWriter();
$reader = $lwHelper->getReader();

// Remove all the blanks
$writer->delete($syncTable, 'newsletter_subscriber_id is null and customer_entity_id is null and email is null');

// Create link records for all existing sync flags
$syncs = $reader->select()->from($syncTable);
$syncs = $syncs->query();
$rows = $syncs->rowCount();

$fh = fopen('php://stdout','w') or die($php_errormsg);;
fwrite($fh, "Total rows: $rows\n");
fwrite($fh, "                 0%");
$count = 0;
$startTime = microtime(true);

Mage::helper('aligent_emarsys')->startEmarsysNewsletterIgnore();
    while ($sync = $syncs->fetchObject()) {
        $subId = 0;
        if ($sync->customer_entity_id) {
            $sql = "select * from $customerTable where entity_id=?";
            $data = $reader->fetchRow($sql, $sync->customer_entity_id);
            $customer = Mage::getModel('customer/customer');
            $customer->addData($data);
            if($customer->getData('store_id')===null) $customer->setData('store_id', 0);  // this really shouldn't happen.

            $newSub = Mage::helper('aligent_emarsys')->ensureCustomerNewsletter($customer);

            $subId = $newSub->getId();
        } else {
            $subId = $sync->newsletter_subscriber_id;
        }

        try{
            $writer->insert($tableName, array(
                'ae_id' => $sync->id,
                'subscriber_id' => $subId
            ));
        }catch(\Exception $e){
            // Some of these may fail due to duplicate key constraints, and that's OK.  Ignore it.

        }
        $perRecord = ( microtime(true) - $startTime ) / ($count);
        $minsToGo = floor($perRecord * ($rows - $count)); //(($perRecord * ($rows - $count)) / 1000);

        $minsToGo = gmdate("H:i:s", $minsToGo);
        $count++;
        $percent = number_format(($count / $rows) * 100, 2);
        fwrite($fh, "\033[20D");      // Move 20 characters backward
        $padded = str_pad($percent.'', 8, ' ', STR_PAD_LEFT);
        $padded .= "% " . str_pad($minsToGo.'', 10, ' ', STR_PAD_RIGHT);
        fwrite( $fh, $padded);
    }
Mage::helper('aligent_emarsys')->endEmarsysNewsletterIgnore();

fwrite($fh, "\n");
fwrite($fh, "Finished in " . gmdate("H:i:s", (microtime(true)-$startTime)) . "\n");
fclose($fh);

$connection->dropColumn($aligentEmarsysTable, 'customer_entity_id');
$connection->dropColumn($aligentEmarsysTable, 'newsletter_subscriber_id');

$installer->endSetup();

function createIndex($installer, $tableName, $columns, $primary, $unique){
    $connection = $installer->getConnection();

    $indexType = ($primary) ? Varien_Db_Adapter_Interface::INDEX_TYPE_PRIMARY : Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX;

    $connection->addIndex(
        $tableName,
        $installer->getIdxName($tableName, $columns, $indexType),
        $columns,
        array('type' => $indexType, 'unique'=>$unique)
    );

}