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
     * Locate, or if necessary, create, an Aligent_Emarsys_Remote_System_Sync_Flags record and associated
     * linking record for a given newsletter subscription ID.
     *
     * @param $id The newsletter subscription ID
     * @param bool $emarsysFlag
     * @param bool $harmonyFlag
     * @param null $firstName
     * @param null $lastName
     * @param null $gender
     * @param null $dob
     * @param null $country
     * @return null|Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function ensureNewsletterSyncRecord($id, $emarsysFlag = true, $harmonyFlag = true, $firstName = null, $lastName = null, $gender = null, $dob = null, $country = null){
        $subscriber = Mage::getModel('newsletter/subscriber')->setStoreId(Mage::app()->getStore()->getId())->load($id);
        if(!$subscriber->getId()){
            return null;// If we weren't passed a valid newsletter subscriber ID, just bail
        }

        $aeLink = Mage::getModel('aligent_emarsys/aeNewsletters')->load($id, 'subscriber_id');
        if($aeLink->getId()){
            return Mage::getModel("aligent_emarsys/remoteSystemSyncFlags")->load($aeLink->getId());
        }else{
            $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags');
            $remoteSync->setHarmonySyncDirty($harmonyFlag);
            $remoteSync->setEmarsysSyncDirty($emarsysFlag);
            if($firstName) $remoteSync->setFirstName($firstName);
            if($lastName) $remoteSync->setLastName($lastName);
            if($dob) $remoteSync->setDob($dob);
            if($gender) $remoteSync->setGender($gender);
            if($country) $remoteSync->setCountry($country);
            $remoteSync->setEmail($subscriber->getSubscriberEmail());
            $remoteSync->save();
            $remoteSync->linkSubscriber($id);
            return $remoteSync;
        }
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
            $aeLink->setId($this->getId());
            $aeLink->save();
            return $aeLink;
        }

    }
}