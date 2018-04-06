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
    protected $_startTime;
    protected $_total;
    protected $_count;
    protected $_stdOut;

    protected function getHelper(){
        return Mage::helper('aligent_emarsys');
    }

    public function exportEmarsysData(){
        /** @var $emarsysHelper Aligent_Emarsys_Helper_Emarsys l*/
        $emarsysHelper = Mage::helper('aligent_emarsys/emarsys');
        $helper = $this->getHelper();
        $helper->log("Emarsys export started", 1);

        /** @var $news Mage_Newsletter_Model_Subscriber */
        $customers = $this->getExportCustomersQuery(false);

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
                $syncData = $helper->ensureCustomerSyncRecord($customer->getId());
                $syncData->setEmarsysSyncDirty(false);
                $syncData->setEmarsysId($result->getData()['id']);
                $syncData->save();
            }else{
                $helper->log("Invalid response:" . $result->getReplyText());
            }
        }

        $subscribers = $this->getExportSubscribersQuery(false);
        foreach($subscribers as $subscriber){
            $storeId = $subscriber->getStoreId();
            if(!$helper->isSubscriptionEnabled($storeId)) {
                $helper->log("Skip subscriber " . $subscriber->getSubscriberId());
                continue;
            }
            $helper->log("Update subscriber " . $subscriber->getSubscriberId());

            $data = $emarsysHelper->getSubscriberData($subscriber);
            $result = $eClient->updateContactAndCreateIfNotExists($data);
            if($result->getReplyCode()==0){
                $this->_pendingEmarsysDataItems[] = $subscriber->getSyncId();
                $syncData = $helper->ensureNewsletterSyncRecord($subscriber->getId());
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

        $timePeriod = $this->getHelper()->getEmarsysChangePeriod();
        $timeString = gmdate('Y-m-d H:i:s') . ' -' . $timePeriod . ' hours';
        $emClient->exportChangesSince(date('Y-m-d H:i:s', strtotime($timeString) ), $url);
    }

    public function importHarmonyData(){
        // Not implemented due to requirement change
    }

    public function exportHarmonyData()
    {
        try {
            $helper = $this->getHelper();
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
        $helper = $this->getHelper();
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
            $fileName = date('Y-m-d-H-i-s-') . 'magento';
            $dir = Mage::getBaseDir('var') . "/harmony_export";
            if(!file_exists($dir)) mkdir($dir);
            file_put_contents($dir . "/$fileName", $fixedWidthData);
            $client->putFromString($fileName, $fixedWidthData);
            return true;
        }catch(\Exception $e){
            return false;
        }
    }

    protected function getExportSubscribersQuery($isHarmony = true){
        $remoteLinkTable = Mage::getModel('aligent_emarsys/aeNewsletters')->getResource()->getMainTable();
        $remoteSystemTable = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getResource()->getMainTable();

        $subscribers = Mage::getModel("newsletter/subscriber")->getCollection();
        $subscribers->getSelect()->joinLeft(['ael'=>$remoteLinkTable], 'ael.subscriber_id=main_table.subscriber_id',['ae_subscriber_id'=>'subscriber_id']);
        $subscribers->getSelect()->joinLeft(['ae'=>$remoteSystemTable], 'ael.ae_id=ae.id', ['sync_id'=>'id']);

        $where = '(customer_id is null OR customer_id=0) AND ';
        if($isHarmony) {
            $where .= "(harmony_sync_dirty = 1 OR harmony_sync_dirty is null)";
        }else{
            $where .= "(emarsys_sync_dirty = 1 OR emarsys_sync_dirty is null)";
        }
        $subscribers->getSelect()->where($where);

        return $subscribers;
    }

    protected function getExportCustomersQuery($isHarmony = true){
        $remoteLinkTable = Mage::getModel('aligent_emarsys/aeNewsletters')->getResource()->getMainTable();
        $remoteSystemTable = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getResource()->getMainTable();
        $newsletterTable = Mage::getModel('newsletter/subscriber')->getResource()->getMainTable();

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

        $customers->getSelect()->joinLeft(['ns'=>$newsletterTable], 'e.entity_id=ns.customer_id');
        $customers->getSelect()->joinLeft(['ael'=>$remoteLinkTable], 'ns.subscriber_id=ael.subscriber_id');
        $customers->getSelect()->joinLeft(['ae'=>$remoteSystemTable], 'ael.ae_id=ae.id',['sync_id'=>'id','harmony_id'=>'harmony_id', 'emarsys_id'=>'emarsys_id']);

        if($isHarmony){
            $customers->getSelect()->where('(harmony_sync_dirty = 1 OR harmony_sync_dirty is null)');
        }else{
            $customers->getSelect()->where('(emarsys_sync_dirty = 1 OR emarsys_sync_dirty is null)');
        }

        $this->getHelper()->log("Customers with SQL " . $customers->getSelect());
        return $customers;
    }

    protected function getHarmonyExportData( $fileName = 'php://temp'){
        $this->_pendingHarmonyDataItems = array();
        $helper = $this->getHelper();

        if($fileName != 'php://temp'){
            $helper->log("Creating $fileName");
            $handle = fopen($fileName, 'w');
            if(!$handle) throw new Exception("Unable to write to $fileName");
            fclose($handle);
        }
        $helper->log("Opening handle to $fileName");
        $handle = fopen($fileName, 'rw+');
        $outputFile = new Aligent_Emarsys_Model_HarmonyDiaryWriter($handle);

        $customers = $this->getExportCustomersQuery(true);

        $reader = Mage::helper('aligent_emarsys/lightweightDataHelper')->getReader();
        $customerQuery = $customers->getSelect();
        $result = $reader->query($customerQuery);

        $total = $reader->query($customerQuery->reset('columns')->columns(['count(*)']))->fetchColumn();

        $this->startProgress($total, true);
        while($data = $result->fetch() ){
            $customer = Mage::getModel('customer/customer');
            $customer->addData($data);
            $helper->log("Processing customer " . $customer->getId());
            if (!$helper->isSubscriptionEnabled($customer->getStore()->getId())) {
                $helper->log("Skipping");
                $this->logProgress();
                continue;
            }
            if($data->sync_id){
                $syncRecord = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($data->sync_id);
            }else{
                $syncRecord = $helper->ensureCustomerSyncRecord($customer->getId(), false, false);
            }
            if(!in_array($syncRecord->getId(), $this->_pendingHarmonyDataItems)){
                $this->_pendingHarmonyDataItems[] = $syncRecord->getId();
                $harmonyCustomer = new Aligent_Emarsys_Model_HarmonyDiary();
                $harmonyCustomer->fillMagentoCustomerFromData($customer, $syncRecord->getId(), $syncRecord->getHarmonyId());
                $outputFile->write($harmonyCustomer->getDataArray());
            }else{
                Mage::helper('aligent_emarsys')->log("Duplicate sync " . $syncRecord->getId());
            }
            $this->logProgress();
        }
        // Free up the memory that was used with this array.
        unset($customers);
        $customers = null;
        gc_collect_cycles();
        $this->endProgress();

        $subscribers = $this->getExportSubscribersQuery(true);
        $helper->log("Get subscribers with: " . $subscribers->getSelectSql(), 2);
        try {
            $this->startProgress(sizeof($subscribers), true);
            foreach ($subscribers as $subscriber) {
                $helper->log("Processing subscriber " . $subscriber->getSubscriberId());
                if (!$helper->isSubscriptionEnabled($subscriber->getStoreId())) continue;
                $syncRecord = $helper->ensureNewsletterSyncRecord($subscriber->getSubscriberId(),null,null,null,null,null,null,null, $subscriber->getStoreId());
                if(!in_array($syncRecord->getId(), $this->_pendingHarmonyDataItems)){
                    $this->_pendingHarmonyDataItems[] = $syncRecord->getId();
                    $harmonyCustomer = new Aligent_Emarsys_Model_HarmonyDiary();
                    $harmonyCustomer->fillMagentoSubscriber($subscriber);
                    $outputFile->write($harmonyCustomer->getDataArray());
                }else{
                    Mage::helper('aligent_emarsys')->log("Duplicate sync " . $syncRecord->getId());
                }
                $this->logProgress();
            }
            rewind($handle);
            $data = stream_get_contents($handle);
            fclose($handle);
            $this->endProgress();
            $helper->log("Size of data: " . sizeof($data));
            return $data;
        }catch(Exception $e){
            $helper->log("Error: " . $e->getMessage());
            return '';
        }
    }

    protected function startProgress($total, $alsoStdOut = false){
        $this->_total = $total;
        $this->_startTime = microtime(true);
        $this->_count = 0;
        $this->_stdOut = $alsoStdOut ? fopen('php://stdout','w') : null;

        if($this->_stdOut){
            fwrite($this->_stdOut, "\n");
            fwrite($this->_stdOut, str_pad("",100," ", STR_PAD_LEFT));
        }
    }

    protected function logProgress(){
        $currentTime = microtime(true);
        $this->_count++;
        $perRecord = ($currentTime - $this->_startTime) / $this->_count;
        $toGo = gmdate("H:i:s",$perRecord * ($this->_total - $this->_count));
        $percent = round(($this->_count / $this->_total) * 100, 2);

        $message = $percent . "%, " . $this->_count . " of " . $this->_total . ", estimate $toGo";
        str_pad($message, 100 , ' ', STR_PAD_LEFT);
        $this->getHelper()->log($message);
        if($this->_stdOut){
            fwrite($this->_stdOut, "\033[100D");
            fwrite($this->_stdOut, $message);
        }
    }

    protected function endProgress(){
        $this->getHelper()->log("Completed " . $this->_count . " in " . gmdate('H:i:s', microtime(true)-$this->_startTime));
        if($this->_stdOut){
            fwrite($this->_stdOut, "\033[100D");
            fwrite($this->_stdOut, "Completed in " . gmdate('H:i:s', microtime(true)-$this->_startTime) . "\n");
            fclose($this->_stdOut);
            $this->_stdOut = null;
        }
    }
}