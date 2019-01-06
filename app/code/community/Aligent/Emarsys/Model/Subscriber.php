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
     * @param int $storeId
     * @return Aligent_Emarsys_Model_Subscriber
     */
    public function loadByEmail($subscriberEmail, $storeId = null)
    {
        // If subscription is not on a per store basis use default Magento behaviour.
        if (!$this->helper()->isScopeIdUsedForSubscription()) {
            return parent::loadByEmail($subscriberEmail);
        }

        if($storeId === null) $storeId = Mage::app()->getStore()->getId();

        // Since cron runs in the admin store scope, and subscribing to the admin scope does not make sense, ignore admin scope.
        if ($storeId == 0) {
            return parent::loadByEmail($subscriberEmail);
        }

        $item = $this->getCollection()->addFieldToFilter('store_id', $storeId)->addFieldToFilter('subscriber_email', $subscriberEmail)->getFirstItem();

        $this->addData($item->getData());
        return $this;
    }

    /**
     * Returns the associated customer object, if one exists
     *
     * @return Mage_Customer_Model_Customer|null
     */
    public function getCustomer(){
        if(!$this->getCustomerId()) return null;
        $customer = Mage::getModel('customer/customer')->setStore(Mage::app()->getStore($this->getStoreId()))->loadByEmail($this->getSubscriberEmail());
        return ($customer->getId()) ? $customer : null;
    }

    /**
     * @return Aligent_Emarsys_Helper_Data
     */
    protected function helper(){
        if($this->_helper == null){
            $this->_helper = Mage::helper('aligent_emarsys');
        }
        return $this->_helper;
    }
}