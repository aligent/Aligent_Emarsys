<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 1/23/18
 * Time: 12:47 PM
 */

class Aligent_Emarsys_Model_EmarsysRecord {
    protected static $_helper = null;
    protected $_email = null;
    protected $_firstName = null;
    protected $_lastName = null;
    protected $_dob = null;
    protected $_harmonyId = null;
    protected $_id = null;
    protected $_rawData = null;
    protected $_gender = null;
    protected $_country = null;

    protected static function getHelper(){
        if(self::$_helper === null){
            self::$_helper = Mage::helper('aligent_emarsys/emarsys');
        }
        return self::$_helper;
    }

    /**
     * Aligent_Emarsys_Model_EmarsysRecord constructor.
     * @param $client Aligent_Emarsys_Model_EmarsysClient
     * @param $row
     */
    public function __construct($client, $row){
        /** @var Aligent_Emarsys_Helper_Emarsys $emarsysHelper */
        $emarsysHelper = self::getHelper();
        $this->_rawData = $row;

        $this->_email = $this->getAbstractField( $emarsysHelper->getEmailField() );
        $this->_firstName = $this->getAbstractField( $emarsysHelper->getFirstnameField() );
        $this->_lastName = $this->getAbstractField( $emarsysHelper->getLastnameField() );
        $this->_dob = $this->getAbstractField( $emarsysHelper->getDobField() );
        $this->_harmonyId = $this->getAbstractField( $emarsysHelper->getHarmonyIdField() );
        $this->_id = $this->getAbstractField( $client->getFieldId('id') );
        $this->_gender = $this->getAbstractField( $client->getFieldId('gender') );
        $this->_gender = isset($emarsysHelper->getGenderMap(false)[$this->_gender]) ? $emarsysHelper->getGenderMap(false)[$this->_gender] : '';
        $this->_country = $this->getAbstractField( $emarsysHelper->getCountryField() );
    }

    /**
     * The Emarsys ID for this record
     *
     * @return int
     */
    public function getId(){
        return $this->_id;
    }

    /**
     * The Harmony ID for this record
     *
     * @return string|null
     */
    public function getHarmonyId(){
        return $this->_harmonyId;
    }

    /**
     * The email address
     *
     * @return string|null
     */
    public function getEmail(){
        return $this->_email;
    }

    /**
     * The first name
     * @return string|null
     */
    public function getFirstName(){
        return $this->_firstName;
    }

    /**
     * The last name
     * @return string|null
     */
    public function getLastName(){
        return $this->_lastName;
    }

    /**
     * The country
     * @return string|null
     */
    public function getCountry(){
        return $this->_country;
    }

    /**
     * Get the Emarsys subscription status for a given store scope
     *
     * @param $storeId
     * @return int
     */
    public function getSubscriptionStatus($storeId = null){
        $subscriptionField = self::getHelper()->getSubscriptionField($storeId);

        if($this->getAbstractField($subscriptionField)===null){
            return Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE;
        }else{
            return self::getHelper()->unmapSubscriptionValue( $this->getAbstractField( $subscriptionField ) );
        }
    }

    /**
     * The date of birth
     * @return DateTime|null
     */
    public function getDOB(){
        return $this->_dob;
    }

    /**
     * The gender as a descriptive string (e.g 'male' or 'female')
     * @return null|string
     */
    public function getGender(){
        return $this->_gender;
    }

    /**
     * Get the value of an arbitrary field, if present on the record.
     *
     * @param $fieldName string
     * @return null|mixed
     */
    public function getAbstractField($fieldName){
        return isset($this->_rawData[$fieldName]) ? $this->_rawData[$fieldName] : null;
    }

}