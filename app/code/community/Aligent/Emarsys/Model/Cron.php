<?php

use FtpClient\FtpClient;

/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 9/18/17
 * Time: 12:53 PM
 */
class Aligent_Emarsys_Model_Cron {
    protected $_pendingHarmonyDataItems;
    protected $_pendingEmarsysDataItems;

    public function exportEmarsysData(){
        /** @var $emarsysHelper Aligent_Emarsys_Helper_Emarsys l*/
        $emarsysHelper = Mage::helper('aligent_emarsys/emarsys');
        $helper = Mage::helper('aligent_emarsys');
        $helper->log("Emarsys export started", 1);

        /** @var $news Mage_Newsletter_Model_Subscriber */
        $customers = Mage::getModel("customer/customer")->getCollection()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('gender');
        $customers->joinTable( [ 'remote_flags'=>'aligent_emarsys/remoteSystemSyncFlags'],
            'customer_entity_id=entity_id',
            array('sync_id' => 'id'), null, 'left');//->addExpressionAttributeToSelect('sync_id','remote_flags.id', 'sync_id');
        $customers->getSelect()->where('(emarsys_sync_dirty = 1 OR emarsys_sync_dirty is null)');
        $eClient = $emarsysHelper->getClient();
        foreach($customers as $customer){
            $storeId = $customer->getStore()->getId();
            if(!$helper->isSubscriptionEnabled($storeId)){
                $helper->log("Skip customer" . $customer->getId() , 2);
                continue;
            }
            $helper->log("Export customer " . $customer->getId(), 2);

            $data = $emarsysHelper->getCustomerData($customer);
            $helper->log("With data: " . print_r($data, true), 2);
            $result = $eClient->updateContactAndCreateIfNotExists($data);
            if($result->getReplyCode()==0){
                if($customer->getSyncId()) {
                    $this->_pendingEmarsysDataItems[] = $customer->getSyncId();
                    $syncData = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($customer->getSyncId());
                    $helper->log("Mark record " . $syncData->getId() . " as in sync with Emarsys", 2);
                    $syncData->setEmarsysSyncDirty(false);
                    $syncData->setEmarsysId($result->getData()['id']);
                    $syncData->save();
                }else{
                    $helper->log("Create new sync record for customer " . $customer->getId(), 2);
                    $syncData = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags');
                    $syncData->setCustomerEntityId($customer->getId());
                    $syncData->setFirstName($customer->getFirstname());
                    $syncData->setLastName($customer->getLastname());
                    $syncData->save();
                }
            }else{
                $helper->log("Invalid response:" . $result->getReplyText());
            }
        }

        $subscribers = Mage::getModel("newsletter/subscriber")->getCollection();
        $subscribers->getSelect()->joinLeft(
            ['remote_flags' =>'aligent_emarsys_remote_system_sync_flags'],
            'subscriber_id=remote_flags.newsletter_subscriber_id',
            array('sync_id' => 'id'), null);
        $subscribers->getSelect()->where('( (customer_entity_id is null OR customer_entity_id=0) AND (emarsys_sync_dirty = 1 OR emarsys_sync_dirty is null) )');

        foreach($subscribers as $subscriber){
            $storeId = $subscriber->getStoreId();
            if(!$helper->isSubscriptionEnabled($storeId)) {
                $helper->log("Skip subscriber " . $subscriber->getId());
                continue;
            }
            $helper->log("Update subscriber " . $subscriber->getId());

            $data = $emarsysHelper->getSubscriberData($subscriber);
            $result = $eClient->updateContactAndCreateIfNotExists($data);
            if($result->getReplyCode()==0){
                $this->_pendingEmarsysDataItems[] = $subscriber->getSyncId();
                $syncData = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($subscriber->getSyncId());
                $syncData->setEmarsysSyncDirty(false);
                $syncData->setEmarsysId($result->getData()['id']);
                $syncData->save();
            }else{
                $helper->log("Invalid response: " . $result->getReplyText());
            }
        }

    }

    public function importEmarsysData(){
        /** @var $emarsysHelper Aligent_Emarsys_Helper_Emarsys l*/
        $emarsysHelper = Mage::helper('aligent_emarsys/emarsys');
        $emClient = $emarsysHelper->getClient();

        /** Really, all this cron function does is kick off the export.  The main work
         * of dealing with the data is done in IndexController:emarsyscallbackAction
         **/
        $url = Mage::getStoreConfig('web/secure/base_url') . "emarsys/index/emarsyscallback";

        $timePeriod = Mage::helper('aligent_emarsys')->getEmarsysChangePeriod();
        $timeString = gmdate('Y-m-d H:i:s') . ' -' . $timePeriod . ' hours';
        $emClient->exportChangesSince(date('Y-m-d H:i:s', strtotime($timeString) ), $url);
    }

    public function importHarmonyData(){
        // Not implemented due to requirement change
    }

    public function exportHarmonyData()
    {
        try {
            $helper = Mage::helper('aligent_emarsys');
            $helper->log("Harmony export starting");
            if(!$helper->getHarmonyCustomerExportLive()){
                $fileName = Mage::getBaseDir('var') . '/harmony_dump.tab';
                $helper->log("Harmony debugging mode to file $fileName", 2);
                $this->getHarmonyExportData( $fileName );
            }else{
                $helper->log("Harmony LIVE mode", 2);
                $fixedWidthData = $this->getHarmonyExportData();
                if (strlen($fixedWidthData) > 0 && $this->pushHarmonyExportData($fixedWidthData)) {
                    $this->markHarmonyInSync();
                }
            }
        }catch(Exception $e){
            Mage::logException($e);
        }
    }

    protected function markHarmonyInSync()
    {
        $flags = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection()->addFieldToFilter('id', array('in' => $this->_pendingHarmonyDataItems));
        $flags->load();
        foreach ($flags as $flag) {
            $flag->setHarmonySyncDirty(false);
            if( !$flag->getHarmonyId() ) {
                $flag->setHarmonyId(Aligent_Emarsys_Model_HarmonyDiary::generateNamekey($flag->getId()));
            }
            $flag->save();
        }

    }

    protected function pushHarmonyExportData($fixedWidthData){
        /** @var $helper Aligent_Emarsys_Helper_Data */
        $helper = Mage::helper('aligent_emarsys');
        $host = $helper->getHarmonyFTPServer();
        $port = $helper->getHarmonyFTPPort();
        $user = $helper->getHarmonyFTPUsername();
        $pass = $helper->getHarmonyFTPPassword();
        $exportDir = $helper->getHarmonyFTPExportDir();

        try{
            $timeout = 15;
            $client = new FtpClient();
            $client->connect($host, false, $port, $timeout);
            $client->login($user, $pass);
            $client->chdir($exportDir);
            $client->pasv(true);
            $client->putFromString(date('Y-m-d-H-i-s-') . 'magento', $fixedWidthData);
            return true;
        }catch(\Exception $e){
            return false;
        }
    }

    protected function getHarmonyExportCustomersQuery(){
        $remoteSystemTable = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getResource()->getMainTable();
        $customerTable = Mage::getModel('customer/customer')->getResource()->getEntityTable();

        $helper = Mage::helper('aligent_emarsys/lightweightDataHelper');
        $query = $helper->getReader()->select()->from($customerTable)->joinLeft($remoteSystemTable, 'customer_entity_id=entity_id')
            ->where('harmony_sync_dirty=1 OR harmony_sync_dirty is null');

        $customers = Mage::getModel("customer/customer")->getCollection()
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('group_id')
            ->joinAttribute('billing_street', 'customer_address/street', 'default_billing', null, 'left')
            ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
            ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
            ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
            ->joinAttribute('billing_fax', 'customer_address/fax', 'default_billing', null, 'left')
            ->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
            ->joinAttribute('billing_country_code', 'customer_address/country_id', 'default_billing', null, 'left')

            ->joinAttribute('shipping_street', 'customer_address/street', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_postcode', 'customer_address/postcode', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_city', 'customer_address/city', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_telephone', 'customer_address/telephone', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_fax', 'customer_address/fax', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_region', 'customer_address/region', 'default_shipping', null, 'left')
            ->joinAttribute('shipping_country_code', 'customer_address/country_id', 'default_shipping', null, 'left')
            ->joinAttribute('taxvat', 'customer/taxvat', 'entity_id', null, 'left');

        $customers->joinTable( [ 'remote_flags'=>'aligent_emarsys/remoteSystemSyncFlags'],
            'customer_entity_id=entity_id',
            array('sync_id' => 'id', 'harmony_id'=>'harmony_id'), null, 'left');//->addExpressionAttributeToSelect('sync_id','remote_flags.id', 'sync_id');
        $customers->getSelect()->where('(harmony_sync_dirty = 1 OR harmony_sync_dirty is null)');

        return $customers;
    }

    protected function getHarmonyExportData( $fileName = 'php://temp'){
        $this->_pendingHarmonyDataItems = array();
        $helper = Mage::helper('aligent_emarsys');

        if($fileName != 'php://temp'){
            $helper->log("Creating $fileName");
            $handle = fopen($fileName, 'w');
            if(!$handle) throw new Exception("Unable to write to $fileName");
            fclose($handle);
        }
        $helper->log("Opening handle to $fileName");
        $handle = fopen($fileName, 'rw+');
        $outputFile = new Aligent_Emarsys_Model_HarmonyDiaryWriter($handle);

        $customers = $this->getHarmonyExportCustomersQuery();
        $helper->log("Customers with SQL " . $customers->getSelect());

        $count = 0;
        foreach ($customers as $customer) {
            $helper->log("Processing customer " . $customer->getId());
            if (!$helper->isSubscriptionEnabled($customer->getStore()->getId())) {
                continue;
            }

            $this->_pendingHarmonyDataItems[] = $customer->getSyncId();
            $harmonyCustomer = new Aligent_Emarsys_Model_HarmonyDiary();
            $count++;
            $helper->log(round(($count / sizeof($customers)) * 100, 2) . "% $count of " . sizeof($customers));

            if ($customer->getSyncId()) {
                $harmonyCustomer->fillMagentoCustomerFromData($customer, $customer->getSyncId(), $customer->getHarmonyId());
            } else {
                $harmonyCustomer->fillMagentoCustomer($customer);
            }

            if( trim( $harmonyCustomer->name_2) !=='' && $harmonyCustomer->name_2 !== null ) {
                $outputFile->write($harmonyCustomer->getDataArray());
            }
        }
        // Free up the memory that was used with this array.
        unset($customers);
        $customers = null;
        gc_collect_cycles();

        $subscribers = Mage::getModel("newsletter/subscriber")->getCollection();
        $subscribers->getSelect()->joinLeft(
            ['remote_flags' =>'aligent_emarsys_remote_system_sync_flags'],
            'remote_flags.newsletter_subscriber_id=main_table.subscriber_id',
            array('sync_id' => 'id'), null);
        $subscribers->getSelect()->where('( (customer_id is null OR customer_id=0) AND (harmony_sync_dirty = 1 OR harmony_sync_dirty is null) )');
        $helper->log("Get subscribers with: " . $subscribers->getSelectSql(), 2);

        try {
            foreach ($subscribers as $subscriber) {
                $helper->log("Processing subscriber " . $subscriber->getId());
                if (!$helper->isSubscriptionEnabled($subscriber->getStoreId())) continue;
                $this->_pendingHarmonyDataItems[] = $subscriber->getSyncId();
                $harmonyCustomer = new Aligent_Emarsys_Model_HarmonyDiary();
                $harmonyCustomer->fillMagentoSubscriber($subscriber);
                // Harmony isn't OK with blank last names
                if( trim( $harmonyCustomer->name_2) !=='' && $harmonyCustomer->name_2 !== null ){
                    $outputFile->write($harmonyCustomer->getDataArray());
                }
            }
            rewind($handle);
            $data = stream_get_contents($handle);
            fclose($handle);
            $helper->log("Finished");
            $helper->log("Size of data: " . sizeof($data));
            return $data;
        }catch(Exception $e){
            $helper->log("Error: " . $e->getMessage());
            return '';
        }
    }
}