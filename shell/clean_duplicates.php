<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 11/29/17
 * Time: 12:58 PM
 */

require_once 'abstract.php';

class Aligent_Emarsys_Shell_Clean_Duplicates extends Mage_Shell_Abstract {
    protected $_helper = null;

    protected function getHelper(){
        if($this->_helper === null){
            $this->_helper = Mage::helper('aligent_emarsys');
        }
        return $this->_helper;
    }

    // Shell script point of entry
    public function run(){
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

        $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection();
        $items->removeAllFieldsFromSelect();
        $items->getSelect()->columns('max(id) as mId, min(id) as minId')->group('newsletter_subscriber_id')->having('count(id) > 1')->where('newsletter_subscriber_id > 0')->where('customer_entity_id=0');
        $this->getHelper()->log('Remove newsletters with SQL: ' . $items->getSelectSql());
        foreach($items as $item){
            $this->mergeData($item->getData('mId'), $item->getData('minId'));
        }

        try {
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
                $this->getHelper()->log("Updated sync record " . $item->getid());
            }
            $this->getHelper()->log("Get dupes");
            // Now email duplicates
            $items = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getCollection();
            $items->removeAllFieldsFromSelect();
            $items->getSelect()->columns('max(id) as mId, min(id) as minId')->group('email')->having('count(id) > 1')->where('email is not null');
            $this->getHelper()->log('Remove customers with SQL: ' . $items->getSelectSql());
            foreach($items as $item){
                $this->mergeData($item->getData('mId'), $item->getData('minId'));
            }
            $this->getHelper()->log("Merge complete");
        }catch(\Exception $e){
            $this->getHelper()->log("Exception: " . $e);
        }
    }

    protected function mergeData($maxRecordId, $minRecordId){
        $helper = $this->getHelper();

        $maxRec = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($maxRecordId);
        $minRec = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($minRecordId);

        // If both records have a harmony id or both have an emarsys id, we can't merge them.
        if(($maxRec->getHarmonyId() && $minRec->getHarmonyId()) && (strtolower($maxRec->getHarmonyId()) != strtolower($minRec->getHarmonyId()))){
            $helper->log("Unable to merge " . $maxRec->getId() . " and " . $minRec->getId());
            return;
        }

        if(($maxRec->getEmarsysId() && $minRec->getEmarsysId()) && (strtolower($maxRec->getEmarsysId()) != strtolower($minRec->getEmarsysId()))){
            $helper->log("Unable to merge " . $maxRec->getId() . " and " . $minRec->getId());
            return;
        }

        // Minimum record is the one we'll keep, merge data to that
        $fields = ['CustomerEntityId','NewsletterSubscriberId','FirstName','LastName','Email','EmarsysId','HarmonyId','Gender','Dob'];
        foreach($fields as $field){
            $get = 'get' . $field;
            $set = 'set' . $field;
            if($maxRec->{$get}() && !$minRec->$get()) $minRec->$set($maxRec->$get());
        }
        $minRec->setEmarsysSyncDirty(1);
        $minRec->setHarmonySyncDirty(1);
        $minRec->save();

        $helper->log("Merged " . $minRec->getId() . " and " . $maxRec->getId());
        $maxRec->delete();
    }
}

$shell = new Aligent_Emarsys_Shell_Clean_Duplicates();
$shell->run();