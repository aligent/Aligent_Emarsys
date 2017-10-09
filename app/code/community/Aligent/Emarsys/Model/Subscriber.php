<?php

class Aligent_Emarsys_Model_Subscriber extends Mage_Newsletter_Model_Subscriber {
    protected $_helper = null;

    public function sendConfirmationSuccessEmail() {
        if( ! $this->helper()->isSubscriptionEnabled( $this->getStoreId() )) {
            return parent::sendConfirmationSuccessEmail();
        }
        return $this;
    }

    public function sendUnsubscriptionEmail(){
        if(!$this->helper()->isSubscriptionEnabled( $this->getStoreId() )){
            return parent::sendUnsubscriptionEmail();
        }
        return $this;
    }

    /**
     * Load subscriber data from resource model by email
     * Overridden because core doesn't pay attention to store scoping.
     *
     * @param string $subscriberEmail
     * @return Aligent_Emarsys_Model_Subscriber
     */
    public function loadByEmail($subscriberEmail)
    {
        $storeId = Mage::app()->getStore()->getId();
        $item = $this->getCollection()->addFieldToFilter('store_id', $storeId)->addFieldToFilter('subscriber_email', $subscriberEmail)->getFirstItem();

        $this->addData($item->getData());
        return $this;
    }

    protected function helper(){
        if($this->_helper == null){
            $this->_helper = Mage::helper('aligent_emarsys');
        }
        return $this->_helper;
    }
}