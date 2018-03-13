<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 9/20/17
 * Time: 9:45 AM
 */
class Aligent_Emarsys_Helper_Emarsys extends Mage_Core_Helper_Abstract {
    const EMARSYS_SUBSCRIBED = 1;
    const EMARSYS_UNSUBSCRIBED = 2;

    /** @var $_helper Aligent_Emarsys_Helper_Data  */
    protected $_helper = null;
    protected $_genders = null;
    protected $_countries = null;
    protected $_gendersByIndex = null;
    protected $_httpClient = null;
    /** @var $_client Aligent_Emarsys_Model_EmarsysClient */
    protected $_client = null;

    /**
     * @return int
     */
    public function getEmailField(){
        return $this->getClient()->getFieldId('email');
    }

    /**
     * @return int
     */
    public function getFirstnameField()
    {
        return $this->getClient()->getFieldId('firstName');
    }

    /**
     * @return int
     */
    public function getLastnameField()
    {
        return $this->getClient()->getFieldId('lastName');
    }

    /**
     * @return int
     */
    public function getGenderField()
    {
        return $this->getClient()->getFieldId('gender');
    }

    /**
     * @return int
     */
    public function getCountryField()
    {
        return $this->getClient()->getFieldId('country');
    }

    public function getDobField(){
        $dob = $this->getHelper()->getEmarsysDobField();
        if(!is_numeric($dob)) $dob = $this->getClient()->getFieldId('birthDate');
        return $dob;
    }

    public function getHarmonyIdField($store = null){
        return $this->getHelper()->getHarmonyIdField($store);
    }

    /**
     * Get the field used for subscription data in the scope of the given store ID;
     * @param null $storeId
     * @return null|string
     */
    public function getSubscriptionField($storeId = null){
        $subscribedField = $this->getHelper()->getEmarsysAPISubscriptionField($storeId);
        if(!is_numeric($subscribedField) || $subscribedField == -1) $subscribedField = null;

        if($subscribedField==null){
            $subscribedField = $this->getClient()->getFieldId('optin');
        }
        return $subscribedField;
    }

    public function unmapSubscriptionValue($subscribed, $store = null){
        $currentField = $this->getSubscriptionField($store) . '';
        $dftField = $this->getClient()->getFieldId('optin') . '';
        $isDefault = ($currentField === null || $currentField === $dftField);

        if($isDefault){
            switch($subscribed){
                case 1:
                    return Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
                case 2:
                    return Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
                default:
                    return Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE;
            }
        }else {
            // Subscribed can be null or an empty string, and both are equivalent here.
            if($subscribed==null) return Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE;
            return (is_numeric($subscribed) && $subscribed!=0) ? Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED : Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
        }
    }

    public function mapSubscriptionValue($subscribed, $customer){
        if(is_object($customer)) $customer = $customer->getId();
        $isDefault = ($this->getSubscriptionField() === null || $this->getSubscriptionField() === $this->getClient()->getFieldId('optin'));
        if($isDefault){
            return ($subscribed) ? self::EMARSYS_SUBSCRIBED : self::EMARSYS_UNSUBSCRIBED;
        }else {
            return ($subscribed) ? $customer : null;
        }
    }

    /**
     * By default the label for the gender will be the index.
     * ie. 'female'=>2
     *
     * if the label should be the value set $labelAsIndex to false
     * ie. 2=>'female'
     *
     * @param bool $labelAsIndex
     * @return array|null
     */
    public function getGenderMap($labelAsIndex=true){
        if($this->_genders === null){
            $this->_genders = $this->_getFieldMap('gender');
            $this->_gendersByIndex = array();
            foreach($this->_genders as $key => $item){
                $this->_gendersByIndex[$item] = $key;
            }
        }

        return ($labelAsIndex) ? $this->_genders : $this->_gendersByIndex;
    }

    protected function _getCountryMap()
    {
        if ($this->_countries === null) {
            $this->_countries = $this->_getFieldMap('country');
        }

        return $this->_countries;
    }

    protected function _getFieldMap($fieldName)
    {
        $result = $this->getClient()->getFieldChoices($fieldName);
        $collection = array();

        foreach ($result->getData() as $item) {
            $collection[strtolower($item['choice'])] = $item['id'];
        }

        return $collection;
    }

    protected function getCustomerGender($customer){
        return ( !$customer->getGender() ) ? '' : $customer->getResource()->getAttribute('gender')->getFrontend()->getValue($customer);
    }

    protected function mapGenderValue($customer){
        if(is_object($customer)){
            $gender = ($customer->getGender() != null) ? $this->getCustomerGender($customer) : null;
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
     * @param $country string
     * @return string|null
     */
    protected function _mapCountryValue($country)
    {
        if (empty($country)) {
            return null;
        }

        $countryMap = $this->_getCountryMap();
        $country = strtolower($country);
        return isset($countryMap[$country]) ? $countryMap[$country] : null;
    }

    /**
     * Get the generic helper object for our module
     * @return Aligent_Emarsys_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function getHelper(){
        if($this->_helper === null) $this->_helper = Mage::helper('aligent_emarsys');
        return $this->_helper;
    }

    protected function abstractDataFill($customerData, $syncData, $isSubscribed, $gender, $country){
        $subField = $this->getSubscriptionField();
        $data = array(
            $this->getEmailField() => $customerData->getEmail(),
            $this->getFirstnameField() => $customerData->getFirstName(),
            $this->getLastnameField() => $customerData->getLastName(),
            $this->getGenderField() => $this->mapGenderValue($gender),
            $this->getDobField() => $customerData->getDob()
        );

        if($country){
            $data[$this->getCountryField()] = $this->_mapCountryValue($country);
        }

        if($subField) {
            $data[$subField] = $this->mapSubscriptionValue($isSubscribed, $syncData->getId());
        }

        $defaultOptIn = $this->getClient()->getFieldId('optin');
        if(!isset($data[$defaultOptIn])) $data[$defaultOptIn] = true;

        $harmonyField = $this->getHarmonyIdField();
        if($harmonyField && $syncData->getId() ){
            $data[$harmonyField] = Aligent_Emarsys_Model_HarmonyDiary::generateNamekey( $syncData->getId() );
        }
        return $data;
    }

    /**
     * @param $subscriber Mage_Newsletter_Model_Subscriber
     * @return array
     */
    public function getSubscriberData($subscriber){
        $syncData = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($subscriber->getId(), 'newsletter_subscriber_id');
        if(!$syncData || !$syncData->getId()){
            $syncData = $this->getHelper()->ensureNewsletterSyncRecord($subscriber->getId());
        }

        $data = $this->abstractDataFill($syncData, $syncData, $subscriber->getStatus(), $syncData->getGender(), $syncData->getCountry());

        return $data;
    }

    public function getCustomerData($customer){
        $syncData = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($customer->getId(), 'customer_entity_id');

        $subscriber = $this->getHelper()->getCustomerSubscriber($customer);
        $genderValue = $this->getCustomerGender($customer);

        $data = $this->abstractDataFill($customer, $syncData, $subscriber->getStatus(), $genderValue, $syncData->getCountry());

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
     * @param null $country
     * @return \Snowcap\Emarsys\Response
     */
    protected function updateSubscriber(
        $localSyncId, $isSubscribed = true, $email = null, $firstname = null,
        $lastname = null, $dob = null, $gender = null, $country = null
    ) {
        $data = array();

        $subField = $this->getSubscriptionField();
        if($subField) $data[$subField] = $this->mapSubscriptionValue($isSubscribed, $localSyncId);
        if($firstname) $data[$this->getFirstnameField()] = $firstname;
        if($lastname) $data[$this->getLastnameField()] = $lastname;
        if($email) $data[$this->getEmailField()] = $email;
        if($dob) $data[$this->getDobField()] = $dob;
        if($gender) $data[$this->getGenderField()] = $this->mapGenderValue($gender);
        if($country) $data[$this->getCountryField()] = $this->_mapCountryValue($country);

        $harmonyField = $this->getHarmonyIdField();
        if($harmonyField){
            $data[$harmonyField] = Aligent_Emarsys_Model_HarmonyDiary::generateNamekey( $localSyncId );
        }

        $defaultOptIn = $this->getClient()->getFieldId('optin');
        if(!isset($data[$defaultOptIn])) $data[$defaultOptIn] = true;

        return $this->getClient()->updateContactAndCreateIfNotExists($data);
    }

    public function removeSubscriber($localId, $email){
        return $this->updateSubscriber($localId, false, $email);
    }

    public function addSubscriber($localId, $firstname, $lastname, $email, $dob, $gender = null, $country = null){
        try{
            $result = $this->updateSubscriber($localId, true, $email, $firstname, $lastname, $dob, $gender, $country);
            return $result;
        }catch(Exception $e){
            $this->getHelper()->log("Subscription error: " . $e->getMessage());
            return null;
        }
    }
}

