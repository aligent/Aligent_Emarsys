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
        $linkTable = Mage::getModel('aligent_emarsys/aeNewsletters')->getResource()->getMainTable();
        $query = Mage::getModel('newsletter/subscriber')->setStoreId($storeId)->getCollection()
            ->join(array('ae'=>$linkTable), 'ae.subscriber_id=main_table.subscriber_id AND ae.ae_id=' . $this->getId());
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
}