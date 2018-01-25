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
    protected $_subscriptionStatus = null;
    protected $_dob = null;
    protected $_harmonyId = null;
    protected $_id = null;
    protected $_rawData = null;
    protected $_gender = null;

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
        $this->_subscriptionStatus = $emarsysHelper->unmapSubscriptionValue( $this->getAbstractField( $emarsysHelper->getSubscriptionField() ) );
        if($this->_subscriptionStatus == true){
            $this->_subscriptionStatus = Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
        }else{
            $this->_subscriptionStatus = Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
        }

        $this->_gender = $this->getAbstractField( $client->getFieldId('gender') );
        $this->_gender = isset($emarsysHelper->getGenderMap(false)[$this->_gender]) ? $emarsysHelper->getGenderMap(false)[$this->_gender] : '';
    }

    public function getId(){
        return $this->_id;
    }

    public function getHarmonyId(){
        return $this->_harmonyId;
    }

    public function getEmail(){
        return $this->_email;
    }

    public function getFirstName(){
        return $this->_firstName;
    }

    public function getLastName(){
        return $this->_lastName;
    }

    public function getSubscriptionStatus(){
        return $this->_subscriptionStatus;
    }

    public function getDOB(){
        return $this->_dob;
    }

    public function getGender(){
        return $this->_gender;
    }

    public function getAbstractField($fieldName){
        return isset($this->_rawData[$fieldName]) ? $this->_rawData[$fieldName] : null;
    }

}