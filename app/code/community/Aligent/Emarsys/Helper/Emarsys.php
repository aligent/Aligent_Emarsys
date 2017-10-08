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

    public function getHarmonyIdField(){
        return $this->getHelper()->getHarmonyIdField();
    }

    public function getSubscriptionField(){
        $subscribedField = $this->getHelper()->getEmarsysAPISubscriptionField();
        if(!is_numeric($subscribedField) || $subscribedField == -1) $subscribedField = $this->getClient()->getFieldId('optin');
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
        if(is_object($customer)){
            $gender = ($customer->getGender() != null) ? $customer->getResource()->getAttribute('gender')->getFrontend()->getValue($customer) : null;
        }else{
            $gender = $customer;
        }
        if($gender){
            $genders = $this->getGenderMap();
            $gender = strtolower($gender);
            return isset($genders[$gender]) ? $genders[$gender] : null;
        }else{
            return null;
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

        $harmonyField = $this->getHarmonyIdField();
        if($harmonyField){
            $syncId = $this->getHelper()->ensureCustomerSyncRecord($customer->getId())->getId();
            $data[$harmonyField] = Aligent_Emarsys_Model_HarmonyDiary::generateNamekey( $syncId );
        }
        return $data;
    }

    public function getClient($emUser = null, $emPass = null){
        if($this->_client === null){
            $this->_client = Aligent_Emarsys_Model_EmarsysClient::create($emUser, $emPass);
        }
        return $this->_client;
    }

    /**
     * Convenience method for data updates to Emarsys.
     *
     * @param $localSyncId
     * @param bool $isSubscribed
     * @param null $firstname
     * @param null $lastname
     * @param null $email
     * @param null $dob
     * @param null $gender
     * @return \Snowcap\Emarsys\Response
     */
    protected function updateSubscriber($localSyncId, $isSubscribed = true, $email = null, $firstname = null, $lastname = null, $dob = null, $gender = null){
        $data = array(
            $this->getSubscriptionField() => $this->mapSubscriptionValue($isSubscribed, $localSyncId)
        );
        if($firstname) $data[$this->getFirstnameField()] = $firstname;
        if($lastname) $data[$this->getLastnameField()] = $lastname;
        if($email) $data[$this->getEmailField()] = $email;
        if($dob) $data[$this->getDobField()] = $dob;
        if($gender) $data[$this->getGenderField()] = $this->mapGenderValue($gender);

        $harmonyField = $this->getHarmonyIdField();
        if($harmonyField){
            $data[$harmonyField] = Aligent_Emarsys_Model_HarmonyDiary::generateNamekey( $localSyncId );
        }
        return $this->getClient()->updateContactAndCreateIfNotExists($data);
    }

    public function removeSubscriber($localId, $email){
        return $this->updateSubscriber($localId, false, $email);
    }

    public function addSubscriber($localId, $firstname, $lastname, $email, $dob, $gender = null){
        try{
            $result = $this->updateSubscriber($localId, true, $email, $firstname, $lastname, $dob, $gender);
            return $result;
        }catch(Exception $e){
            return null;
        }
    }
}

