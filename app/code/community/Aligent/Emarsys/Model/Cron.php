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
            if(!$helper->isSubscriptionEnabled($storeId)) continue;

            $data = $emarsysHelper->getCustomerData($customer);
            $result = $eClient->updateContactAndCreateIfNotExists($data);
            if($result->getReplyCode()==0){
                $this->_pendingEmarsysDataItems[] = $customer->getSyncId();
                $syncData = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($customer->getSyncId());
                $syncData->setEmarsysSyncDirty(false);
                $syncData->setEmarsysId($result->getData()['id']);
                $syncData->save();
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
            if(!$helper->isSubscriptionEnabled($storeId)) continue;

            $data = $emarsysHelper->getSubscriberData($subscriber);
            $result = $eClient->updateContactAndCreateIfNotExists($data);
            if($result->getReplyCode()==0){
                $this->_pendingEmarsysDataItems[] = $subscriber->getSyncId();
                $syncData = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($subscriber->getSyncId());
                $syncData->setEmarsysSyncDirty(false);
                $syncData->setEmarsysId($result->getData()['id']);
                $syncData->save();
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

        $yesterdayGMT = strtotime(gmdate('Y-m-d H:i:s') . ' -1 day');
        $emClient->exportChangesSince(date('Y-m-d H:i:s',$yesterdayGMT), $url);
    }

    public function importHarmonyData(){
        // Not implemented due to requirement change
    }

    public function exportHarmonyData()
    {
        try {
            $helper = Mage::helper('aligent_emarsys');
            $fixedWidthData = $this->getHarmonyExportData();
            if($helper->getHarmonyFileDump()){
                $fileName = Mage::getBaseDir() . '/media/harmony_dump.tab';
                file_put_contents($fileName, $fixedWidthData);
            }elseif (strlen($fixedWidthData) > 0 && $this->pushHarmonyExportData($fixedWidthData)) {
                $this->markHarmonyInSync();
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
            $flag->setHarmonyId( Aligent_Emarsys_Model_HarmonyDiary::generateNamekey($flag->getId()) );
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

    protected function getHarmonyExportData(){
        $this->_pendingHarmonyDataItems = array();
        $helper = Mage::helper('aligent_emarsys');

        $handle = fopen('php://temp', 'rw+');
        $outputFile = new Aligent_Emarsys_Model_HarmonyDiaryWriter($handle);

        $customers = Mage::getModel("customer/customer")->getCollection();
        $customers->joinTable( [ 'remote_flags'=>'aligent_emarsys/remoteSystemSyncFlags'],
            'customer_entity_id=entity_id',
            array('sync_id' => 'id'), null, 'left');//->addExpressionAttributeToSelect('sync_id','remote_flags.id', 'sync_id');
        $customers->getSelect()->where('(harmony_sync_dirty = 1 OR harmony_sync_dirty is null)');

        foreach ($customers as $customer) {
            if(!$helper->isSubscriptionEnabled($customer->getStore()->getId())) continue;

            $this->_pendingHarmonyDataItems[] = $customer->getSyncId();
            $harmonyCustomer = new Aligent_Emarsys_Model_HarmonyDiary();
            $harmonyCustomer->fillMagentoCustomer($customer->getId());
            $outputFile->write($harmonyCustomer->getDataArray());
        }

        $subscribers = Mage::getModel("newsletter/subscriber")->getCollection();
        $subscribers->getSelect()->joinLeft(
            ['remote_flags' =>'aligent_emarsys_remote_system_sync_flags'],
            'remote_flags.newsletter_subscriber_id=main_table.subscriber_id',
            array('sync_id' => 'id'), null);
        $subscribers->getSelect()->where('( (customer_entity_id is null OR customer_entity_id=0) AND (harmony_sync_dirty = 1 OR harmony_sync_dirty is null) )');

        foreach ($subscribers as $subscriber) {
            if(!$helper->isSubscriptionEnabled($subscriber->getStoreId())) continue;
            $this->_pendingHarmonyDataItems[] = $subscriber->getSyncId();
            $harmonyCustomer = new Aligent_Emarsys_Model_HarmonyDiary();
            $harmonyCustomer->fillMagentoSubscriber($subscriber->getId());
            $outputFile->write($harmonyCustomer->getDataArray());
        }
        rewind($handle);
        $data = stream_get_contents($handle);
        fclose($handle);
        return $data;
    }
}