<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 11/29/17
 * Time: 12:58 PM

 * IMPORTANT NOTE: This was refactored to use the core/resource connections core_read and core_write for
 * speed reasons (it's significantly faster).  While this does bypass model getters and setters, the fields
 * in question do not have getters and setters that we are concerned about for this exercise.  We're also
 * fixing data duplication and don't want event observers firing while we clean it up.
 */

require_once 'abstract.php';

class Aligent_Emarsys_Shell_Clean_Duplicates extends Mage_Shell_Abstract {
    protected $_helper = null;
    protected $_store = null;
    protected $_reader = null;
    protected $_writer = null;
    protected $_resource = null;
    protected $_newsletterTable = null;
    protected $_aligentTable = null;

    // Shell script point of entry
    public function run(){
        try {
            if($this->_store !== null){
                $this->moveCustomersToStore();
                $this->moveNewslettersToStore();
            }
            $this->dedupeFromCustomerId();
            $this->dedupeFromNewsletterId();
            $this->updateSyncFromCustomer();
            $this->dedupeFromEmailAddress();
            $this->dedupeNewsletterSubscriberTable();

            $this->getHelper()->log("Merge complete");
        }catch(\Exception $e){
            $this->getHelper()->log("Exception: " . $e);
        }
    }

    protected function getHelper(){
        if($this->_helper === null){
            $this->_helper = Mage::helper('aligent_emarsys');
        }
        return $this->_helper;
    }

    protected function dedupeFromCustomerId(){
        // Remove any of the duplicate records that are going to break the scripts
        $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection();
        $items->removeAllFieldsFromSelect();
        $items->getSelect()
            ->columns('max(id) as mId, min(id) as minId')
            ->group('customer_entity_id')
            ->having('count(id) > 1')
            ->where('customer_entity_id > 0');

        $this->getHelper()->log('Remove customers with SQL: ' . $items->getSelectSql());

        foreach($items as $item){
            $this->mergeData($item->getData('mId'), $item->getData('minId'));
        }
    }

    protected function dedupeFromNewsletterId(){
        $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection();
        $items->removeAllFieldsFromSelect();
        $items->getSelect()->columns('max(id) as mId, min(id) as minId')->group('newsletter_subscriber_id')->having('count(id) > 1')->where('newsletter_subscriber_id > 0')->where('customer_entity_id=0');
        $this->getHelper()->log('Remove newsletters with SQL: ' . $items->getSelectSql());
        foreach($items as $item){
            $this->mergeData($item->getData('mId'), $item->getData('minId'));
        }
    }

    protected function updateSyncFromCustomer(){
        $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection();
        $items->getSelect()->where('customer_entity_id > 0 and email is null');
        foreach($items as $item){
            $customer = Mage::getModel('customer/customer')->load($item->getCustomerEntityId());
            $item->setFirstName($customer->getFirstName());
            $item->setLastName($customer->getLastName());
            $item->setEmail($customer->getEmail());
            $item->setHarmonySyncDirty(1);
            $item->setEmarsysSyncDirty(1);
            $item->save();
            $this->getHelper()->log("Updated sync record " . $item->getId());
        }
    }

    protected function dedupeFromEmailAddress(){
        $this->getHelper()->log("Get dupes");
        // Now email duplicates
        $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection();
        $items->removeAllFieldsFromSelect();
        $items->getSelect()->columns('max(id) as mId, min(id) as minId')->group('email')->having('count(id) > 1')->where('email is not null');
        $this->getHelper()->log('Remove customers with SQL: ' . $items->getSelectSql());
        foreach($items as $item){
            $this->mergeData($item->getData('mId'), $item->getData('minId'));
        }
    }

    protected function dedupeNewsletterSubscriberTable(){
        $this->getHelper()->log("Get newsletter dupes");
        // Now email duplicates
        $items = Mage::getModel('newsletter/subscriber')->getCollection();
        $items->removeAllFieldsFromSelect();
        $items->getSelect()->columns('max(subscriber_id) as mId, min(subscriber_id) as minId')->group('subscriber_email')->having('count(subscriber_id) > 1')->where('subscriber_email is not null');
        $this->getHelper()->log('Remove subscribers with SQL: ' . $items->getSelectSql());
        foreach($items as $item){
            $this->mergeSubscriberData($item->getData('mId'), $item->getData('minId'));
        }
    }

    protected function moveNewslettersToStore(){
        $this->getWriter()->update($this->_newsletterTable, ['store_id'=>$this->_store->getId()], 'store_id=' . Mage_Core_Model_App::ADMIN_STORE_ID);
    }

    protected function moveCustomersToStore(){
        // Walk over and update.
        $resource = Mage::getSingleton('core/resource');
        $customer = Mage::getModel('customer/customer');

        $table = $customer->getResource()->getEntityTable();
        $resource->getConnection('core_write')->update($table, ['store_id'=>$this->_store->getId()], 'store_id=' . Mage_Core_Model_App::ADMIN_STORE_ID);
    }

    protected function getResource(){
        return Mage::getSingleton('core/resource');
    }

    protected function getWriter(){
        if($this->_writer === null){
            $this->_writer = $this->getResource()->getConnection('core_write');
        }
        return $this->_writer;
    }

    protected function getReader(){
        if($this->_reader===null){
            $this->_reader = $this->getResource()->getConnection('core_read');
        }
        return $this->_reader;
    }

    protected function loadSubscriberData($fromId){
        $reader = $this->getReader();

        $table = $this->_newsletterTable;
        return $reader->select()->from($table)->where('subscriber_id=' . $fromId)->query()->fetch();
    }

    protected function loadAligentDataFromNewsletter($fromId){
        return $this->getReader()->select()->from($this->_aligentTable)->where('newsletter_subscriber_id=' . $fromId)->query()->fetch();
    }

    protected function loadAligentData($fromId){
        return $this->getReader()->select()->from($this->_aligentTable)->where('id=' . $fromId)->query()->fetch();
    }

    protected function mergeSubscriberData($maxRecordId, $minRecordId){
        $helper = $this->getHelper();

        $maxRec = $this->loadSubscriberData($maxRecordId);
        $minRec = $this->loadSubscriberData($minRecordId);

        if($maxRec['store_id'] != $minRec['store_id']){
            if($minRec['store_id'] == Mage_Core_Model_App::ADMIN_STORE_ID){
                $minRec['subscriber_status'] = $maxRec['subscriber_status'];

                if( $this->_store !== null ){
                    $minRec['store_id'] = $this->_store->getId();
                }
            }
        }

        // We'll always take the smaller one.
        if($maxRec['customer_id'] > 0 && $minRec['customer_id'] == 0){
            $minRec['customer_id'] = $maxRec['customer_id'];
        }

        $this->getWriter()->update($this->_newsletterTable, $minRec, 'subscriber_id='.$minRecordId);

        $syncRecord = $this->loadAligentDataFromNewsletter($maxRecordId);
        if(is_array($syncRecord) && isset($syncRecord['id'])){
            $syncRecord['newsletter_subscriber_id'] = $minRecordId;
            $this->getWriter()->update($this->_aligentTable, $syncRecord, 'id=' . $syncRecord['id']);
        }

        $this->getWriter()->delete($this->_newsletterTable, 'subscriber_id='.$maxRecordId);
    }

    protected function mergeData($maxRecordId, $minRecordId){
        $helper = $this->getHelper();

        $maxRec = $this->loadAligentData($maxRecordId);
        $minRec = $this->loadAligentData($minRecordId);

        // If both records have a harmony id or both have an emarsys id, we can't merge them.
        if(($maxRec['harmony_id'] && $minRec['harmony_id']) && (strtolower($maxRec['harmony_id']) != strtolower($minRec['harmony_id']))){

            if(substr($maxRec['harmony_id'], 0, 4)!=='SWG0') {
                $helper->log("Unable to merge " . $maxRec['id'] . " and " . $minRec['id']);
                $helper->log("Substr is " . substr($maxRec['harmony_id'], 0, 4));
                return;
            }
        }

        if(($maxRec['emarsys_id'] && $minRec['emarsys_id']) && (strtolower($maxRec['emarsys_id']) != strtolower($minRec['emarsys_id']))){
            $helper->log("Unable to merge " . $maxRec['id'] . " and " . $minRec['id']);
            return;
        }

        // Minimum record is the one we'll keep, merge data to that
        $fields = ['customer_entity_id','newsletter_subscriber_id','first_name','last_name','email','emarsys_id','harmony_id','gender','dob'];
        foreach($fields as $field){
            if($maxRec[$field] && ($minRec[$field]=='')){
                $minRec[$field] = $maxRec[$field];
            }
        }
        $minRec['emarsys_sync_dirty'] = 1;
        $minRec['harmony_sync_dirty'] = 1;
        $this->getWriter()->update($this->_aligentTable, $minRec, 'id=' . $minRec['id']);

        $helper->log("Merged " . $minRec['id'] . " and " . $maxRec['id']);
        $this->getWriter()->delete($this->_aligentTable, 'id='.$maxRecordId);
    }

    public function __construct() {
        parent::__construct();
        set_time_limit(0);
        $this->_store = $this->getArg('store');
        if($this->_store !== null){
            $this->_store = Mage::getModel('core/store')->load($this->_store);
            if( !$this->_store->getId() ){
                $this->_store = null;
            }
        }
        $this->_newsletterTable = Mage::getModel('newsletter/subscriber')->getResource()->getMainTable();
        $this->_aligentTable = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getResource()->getMainTable();
    }
}

$shell = new Aligent_Emarsys_Shell_Clean_Duplicates();
$shell->run();