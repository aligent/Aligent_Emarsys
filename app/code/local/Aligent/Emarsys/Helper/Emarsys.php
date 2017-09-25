<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 9/20/17
 * Time: 9:45 AM
 */
class Aligent_Emarsys_Helper_Emarsys extends Mage_Core_Helper_Abstract {
    /** @var $_helper Aligent_Emarsys_Helper_Data  */
    protected $_helper = null;
    protected $_genders = null;
    protected $_httpClient = null;
    /** @var $_client Aligent_Emarsys_Model_EmarsysClient */
    protected $_client = null;

    public function getEmailField(){
        return $this->getClient()->getFieldId('email');
    }

    public function getFirstnameField()
    {
        return $this->getClient()->getFieldId('firstName');
    }

    public function getLastnameField()
    {
        return $this->getClient()->getFieldId('lastName');
    }

    public function getGenderField()
    {
        return $this->getClient()->getFieldId('gender');
    }

    public function getDobField(){
        $dob = $this->getHelper()->getEmarsysDobField();
        if(!is_numeric($dob)) $dob = $this->getClient()->getFieldId('birthDate');
        return $dob;
    }

    public function getSubscriptionField(){
        $subscribedField = $this->getHelper()->getEmarsysAPISubscriptionField();
        if(!is_numeric($subscribedField) || $subscribedField==-1) $subscribedField = $this->getClient()->getFieldId('optin');
        return $subscribedField;
    }

    public function unampSubscriptionValue($subscribed){
        $isDefault = ($this->getSubscriptionField() == $this->getClient()->getFieldId('optin') );
        if($isDefault){
            return (strtolower($subscribed)=='true');
        }else {
            return (is_numeric($subscribed) && $subscribed!=0);
        }
    }

    public function mapSubscriptionValue($subscribed, $customer){
        if(is_object($customer)) $customer = $customer->getId();
        $isDefault = ($this->getSubscriptionField() == $this->getClient()->getFieldId('optin') );
        if($isDefault){
            return ($subscribed) ? 1 : 2;
        }else {
            return ($subscribed) ? $customer : null;
        }
    }

    protected function getGenderMap(){
        if($this->_genders===null){
            $result = $this->getClient()->getFieldChoices('gender');
            $this->_genders = array();
            foreach($result->getData() as $item){
                $this->_genders[strtolower($item['choice'])] = $item['id'];
            }
        }
        return $this->_genders;
    }

    protected function mapGenderValue($customer){
        $gender = ($customer->getGender() != null) ? $customer->getResource()->getAttribute('gender')->getFrontend()->getValue($customer) : null;
        if($gender){
            $genders = $this->getGenderMap();
            $gender = strtolower($gender);
            return isset($genders[$gender]) ? $genders[$gender] : null;
        }
    }

    /**
     * Get the generic helper object for our module
     * @return Aligent_Emarsys_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function getHelper(){
        if($this->_helper === null) $this->_helper = Mage::helper('aligent_emarsys');
        return $this->_helper;
    }

    public function getCustomerData($customer){
        $data = array(
            $this->getEmailField() => $customer->getEmail(),
            $this->getSubscriptionField() => $this->mapSubscriptionValue($this->getHelper()->isCustomerSubscribed($customer), $customer),
            $this->getFirstnameField() => $customer->getFirstname(),
            $this->getLastnameField() => $customer->getLastname(),
            $this->getGenderField() => $this->mapGenderValue($customer),
            $this->getDobField() => $customer->getDob()
        );
        return $data;
    }

    public function getClient(){
        if($this->_client === null){
            $this->_client = Aligent_Emarsys_Model_EmarsysClient::create();
        }
        return $this->_client;
    }

    public function updateSubscriber($localId, $firstname, $lastname, $email, $dob){
        $data = array(
            $this->getEmailField() => $email,
            $this->getFirstnameField() => $firstname,
            $this->getLastnameField() => $lastname,
            $this->getSubscriptionField() => $this->mapSubscriptionValue(true, $localId),
            $this->getDobField() => $dob
        );
        return $this->getClient()->updateContact($data);
    }

    public function removeSubscriber($localId, $email){
        $data = array($this->getEmailField() => $email, $this->getSubscriptionField()=>$this->mapSubscriptionValue(false, $localId));
        $result = $this->getClient()->updateContactAndCreateIfNotExists($data);
        return $result->getData();
    }

    public function addSubscriber($localId, $firstname, $lastname, $email, $dob){
        $data = array(
            $this->getEmailField()=>$email,
            $this->getFirstnameField()=>$firstname,
            $this->getLastnameField()=>$lastname,
            $this->getSubscriptionField()=>$this->mapSubscriptionValue(true, $localId)
        );
        $dobField = $this->getDobField();
        if($dobField) $data[$dobField] = $dob;
        $result = $this->getClient()->updateContactAndCreateIfNotExists($data);
        return $result;
    }
}

