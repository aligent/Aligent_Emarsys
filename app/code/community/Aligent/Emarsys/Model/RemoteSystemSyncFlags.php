<?php

class Aligent_Emarsys_Model_RemoteSystemSyncFlags extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('aligent_emarsys/remoteSystemSyncFlags');
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();
        if($this->isObjectNew()){
            $this->setCreatedAt( Mage::getModel('core/date')->date('Y-m-d H:i:s') );
        }
        $this->setUpdatedAt( Mage::getModel('core/date')->date('Y-m-d H:i:s') );
        return $this;
    }

    /**
     * Ensure a link exists between this remote sync record and the given subscriber ID
     * @param $id Subscriber ID
     * @return Aligent_Emarsys_Model_AeNewsletters
     */
    public function linkSubscriber($id){
        $aeLink = Mage::getModel('aligent_emarsys/aeNewsletters')->load($id, 'subscriber_id');
        if($aeLink->getId()) {
            return Mage::getModel("aligent_emarsys/remoteSystemSyncFlags")->load($aeLink->getId());
        }else{
            $aeLink->setSubscriberId($id);
            $aeLink->setAeId($this->getId());
            $aeLink->save();
            return $aeLink;
        }
    }

    /**
     * Removes the link between the given subscriber and this sync record, if it exists.
     * @param $id
     */
    public function unlinkSubscriber($id){
        $aeLink = Mage::getModel('aligent_emarsys/aeNewsletters')->load($id, 'subscriber_id');
        if($aeLink && $aeLink->getAeId()==$this->getId()){
            $aeLink->delete();
        }
    }


    /**
     * Load a record by email address
     *
     * @param $email
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags|null
     */
    public static function loadByEmail($email){
        $linkTable = Mage::getModel('aligent_emarsys/aeNewsletters')->getResource()->getMainTable();

        $query = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter('subscriber_email', $email);
        $query->getSelect()->joinLeft(array('ae'=>$linkTable), 'ae.subscriber_id=main_table.subscriber_id', array('ae_id'=>'ae_id'));
        $item = $query->getFirstItem();

        if($item->getAeId()){
            $sync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags');
            $sync->load($item->getAeId());
            return $sync;
        }else{
            return null;
        }
    }

    public function getSubscriber($storeId){
        $query = Mage::getModel('newsletter/subscriber')->setStoreId($storeId)->getCollection()
            ->join(array('ae'=>'aligent_emarsys/aeNewsletters'), 'ae.subscriber_id=main_table.subscriber_id AND ae.ae_id=' . $this->getId());
        $item = $query->getFirstItem();

        return ($item && $item->getSubscriberId()) ? $item : null;
    }

    /**
     * @param $newEmail string
     */
    public function setEmail($newEmail){
        $oldEmail = $this->getData('email');
        if($oldEmail != $newEmail){
            $this->setData('email', $newEmail);
        }
    }

    /**
     * Gets all subscribers linked to this record
     * @return Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    public function getSubscribers(){
        $query = Mage::getModel('newsletter/subscriber')->getCollection();
        $query->join(array('ae'=>'aligent_emarsys/aeNewsletters'), 'ae.subscriber_id=main_table.subscriber_id AND ae.ae_id=' . $this->getId());
        return $query;
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     */
    public function setCustomerEmail($customer){
        if($customer->getOrigData('email') == $customer->getData('email') || $customer->getOrigData('email') == null) return;

        $existingSubs = $this->getSubscribers();
        if($existingSubs->count() > 1){
            $newRecord = Mage::helper('aligent_emarsys')->ensureCustomerSyncRecord($customer->getId());
            foreach($existingSubs as $sub){
                if($sub->getCustomerId() == $customer->getId()){
                    $this->unlinkSubscriber($sub->getId());
                    $sub->setSubscriberEmail($customer->getEmail());
                    $sub->save();
                    $newRecord->linkSubscriber($sub->getId());
                }
            }
        }else{
            $sub = $existingSubs->getFirstItem();
            $sub->setSubscriberEmail($customer->getEmail());
            $sub->save();
        }
    }
}