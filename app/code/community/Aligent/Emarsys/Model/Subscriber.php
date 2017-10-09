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

    protected function helper(){
        if($this->_helper == null){
            $this->_helper = Mage::helper('aligent_emarsys');
        }
        return $this->_helper;
    }
}