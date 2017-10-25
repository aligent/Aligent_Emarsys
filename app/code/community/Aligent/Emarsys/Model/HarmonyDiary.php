<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 9/15/17
 * Time: 11:06 AM
 */

class Aligent_Emarsys_Model_HarmonyDiary
{
    /** @var  Aligent_Emarsys_Helper_Data  */
    protected $_helper;

    public function __construct(){
        $this->_helper = Mage::helper('aligent_emarsys');
    }

    /**
     * Formats the given value in the appropriate format
     * for the Harmony export.  Currently that's DD-MM-YYYY
     * @param $dateValue string|DateTime
     * @return string
     */
    protected function harmonyDate($dateValue){
        if($dateValue === null || $dateValue === '') return '';

        if(is_string($dateValue) ){
            $dateValue = DateTime::createFromFormat('Y-m-d', $dateValue);
        }
        return $dateValue->format('d-m-Y');

    }

    protected function limitString($string, $length){
        if(strlen($string) > $length){
            $string = substr($string, 0, $length);
        }
        return $string;
    }

    protected function populateAddress($addressId, $fieldMap){
        if (!$addressId) return;
        $address = Mage::getModel('customer/address')->load($addressId);
        $address->getData();

        $this->{$fieldMap[0]} = $this->limitString( $address->getStreet(1), 30 );
        $this->{$fieldMap[1]} = $this->limitString( $address->getStreet(2), 25 );
        $this->{$fieldMap[2]} = $this->limitString( $address->getCity() . ' ' . $address->getRegionCode(), 25 );
        $this->{$fieldMap[3]} = $this->limitString( $address->getCountryModel()->getName(), 20 );
        $this->{$fieldMap[4]} = $this->limitString( $address->getPostcode(), 10 );
    }

    public function fillMagentoBillingAddress($addressId){
        $this->populateAddress($addressId, array('address_1','address_2','address_3','address_4','postcode'));
    }

    public function fillMagentoShippingAddress($addressId){
        $this->populateAddress($addressId, array('deilvery_address_1','deilvery_address_2','deilvery_address_3','deilvery_address_4','deilvery_postcode'));
    }

    protected function ensureSyncData($customer){
        return $this->_helper->ensureCustomerSyncRecord($customer->getId());
    }

    public function fillMagentoSubscriber($subscriberId){
        $subscriber = Mage::getModel('newsletter/subscriber')->load($subscriberId);
        $localSyncData = $this->_helper->ensureNewsletterSyncRecord($subscriberId);

        $this->action = ($localSyncData->getHarmonyId()) ? 'M' : 'A';
        $this->name_1 = $this->limitString( $localSyncData->getFirstName(), 30) ;
        $this->name_2 = $this->limitString( $localSyncData->getLastName(), 30);
        $this->email = $this->limitString($localSyncData->getEmail(), 60);

        $this->date_of_birth = $this->harmonyDate( $localSyncData->getDob());
        $this->{'classification.1'} = $subscriber->getSubscriberStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED ? 'EMAIL' : 'NOEML';
        $this->namekey = Aligent_Emarsys_Model_HarmonyDiary::generateNamekey($localSyncData->getId());
    }

    public function isCustomerSubscribed($customer){
        return $this->_helper->isCustomerSubscribed($customer);

    }

    public function fillMagentoCustomer($customerId)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $localSyncData = $this->ensureSyncData($customer);

        $this->action = ($localSyncData->getHarmonyId()) ? 'M' : 'A';
        $this->name_1 = $this->limitString( $customer->getLastname(), 30 );
        $this->name_2 = $this->limitString( $customer->getFirstname(), 30);
        $this->email = $this->limitString($customer->getEmail(), 60);
        $this->{'telephone.0'} = $this->limitString( $customer->getTelephone(), 20);

        $this->fillMagentoBillingAddress($customer->getDefaultBilling());
        $this->fillMagentoShippingAddress($customer->getDefaultShipping());

        $this->date_of_birth = $this->harmonyDate( $customer->getDob() );
        $this->{'classification.1'} = $this->isCustomerSubscribed($customer) ? 'EMAIL' : 'NOEML';

        $this->namekey = Aligent_Emarsys_Model_HarmonyDiary::generateNamekey($localSyncData->getId());
    }

    public static $fieldProperties = array(
        array(
            'name' => 'format_id',
            'label' => 'format_id',
            'fieldWidth' => 10,
            'default' => 'DRYMNFBS08',
        ),
        array(
            'name' => 'export_number',
            'label' => 'export_number',
            'fieldWidth' => 10,
            'default' => '',
        ),
        array(
            'name' => 'action',
            'label' => 'action',
            'fieldWidth' => 1
        ),
        array(
            'name' => 'namekey',
            'label' => 'Diary Namekey',
            'fieldWidth' => 10
        ),
        array(
            'name' => 'name_1',
            'label' => 'Surname',
            'fieldWidth' => 30,
            'default' => '',
        ),
        array(
            'name' => 'name_2',
            'default' => '',
            'label' => 'First Name',
            'fieldWidth' => 30
        ),
        /* Billing Address */
        array(
            'name' => 'address_1',
            'default' => '',
            'label' => 'Billing Address',
            'fieldWidth' => 30
        ),
        array(
            'name' => 'address_2',
            'default' => '',
            'label' => 'Billing Address 2 ',
            'fieldWidth' => 25
        ),
        array(
            'name' => 'address_3',
            'default' => '',
            'label' => 'Billing Address 3',
            'fieldWidth' => 25
        ),
        array(
            'name' => 'address_4',
            'default' => '',
            'label' => 'Billing Address 4',
            'fieldWidth' => 20
        ),
        array(
            'name' => 'postcode',
            'default' => '',
            'label' => 'Billing Postcode',
            'fieldWidth' => 10
        ),
        /* Shipping Address */
        array(
            'name' => 'delivery_address_1',
            'default' => '',
            'label' => 'Shipping Address',
            'fieldWidth' => 30
        ),
        array(
            'name' => 'delivery_address_2',
            'default' => '',
            'label' => 'Shipping Address 2 ',
            'fieldWidth' => 25
        ),
        array(
            'name' => 'delivery_address_3',
            'default' => '',
            'label' => 'Shipping Address 3',
            'fieldWidth' => 25
        ),
        array(
            'name' => 'delivery_address_4',
            'default' => '',
            'label' => 'Shipping Address 4',
            'fieldWidth' => 20
        ),
        array(
            'name' => 'delivery_postcode',
            'default' => '',
            'label' => 'Shipping Postcode',
            'fieldWidth' => 10
        ),
        array(
            'name' => 'agent',
            'default' => '',
            'label' => 'Agent',
            'fieldWidth' => 5,
            'writeProcessor' => array('Aligent_Emarsys_Model_HarmonyDiary', 'castInt')
        ),
        array(
            'default' => '',
            'name' => 'telephone.0',
            'default' => '',
            'label' => 'Telephone (home)',
            'fieldWidth' => 20
        ),
        array(
            'default' => '',
            'name' => 'telephone.1',
            'label' => 'Mobile',
            'fieldWidth' => 20
        ),
        array(
            'default' => '',
            'name' => 'telephone.3',
            'label' => 'Telephone (work)',
            'fieldWidth' => 20
        ),
        array(
            'default' => '',
            'name' => 'prospect',
            'label' => 'Prospect',
            'fieldWidth' => 1
        ),
        array(
            'default' => '',
            'name' => 'fax',
            'label' => 'Fax Number',
            'fieldWidth' => 20
        ),
        array(
            'default' => '',
            'name' => 'text_1',
            'label' => 'Text 1',
            'fieldWidth' => 30
        ),
        array(
            'default' => '',
            'name' => 'text_2',
            'label' => 'Text 2',
            'fieldWidth' => 30
        ),
        array(
            'default' => '',
            'name' => 'email',
            'label' => 'E-mail',
            'fieldWidth' => 60
        ),
        array(
            'default' => '',
            'name' => 'title',
            'label' => 'Title',
            'fieldWidth' => 7
        ),
        array(
            'default' => '',
            'name' => 'date_of_birth',
            'label' => 'Date of birth',
            'fieldWidth' => 10
        ),
        array(
            'default' => '',
            'name' => 'first_contact',
            'label' => 'First Contact Date',
            'fieldWidth' => 10
        ),
        array(
            'name' => 'debtor_namekey',
            'label' => 'Debtor namekey',
            'fieldWidth' => 10
        ),
        array(
            'name' => 'user_id',
            'label' => 'User ID',
            'fieldWidth' => 10
        ),
        array(
            'name' => 'terminal_id',
            'label' => 'Terminal Id',
            'fieldWidth' => 11
        ),
        array(
            'default' => '',
            'name' => 'category',
            'label' => 'Category',
            'fieldWidth' => 5
        ),
        array(
            'default' => '',
            'name' => 'classification.0',
            'label' => 'Classification 1',
            'fieldWidth' => 5
        ),
        array(
            'default' => '',
            'name' => 'classification.1',
            'label' => 'Classification 2',
            'fieldWidth' => 5
        ),
        array(
            'default' => '',
            'name' => 'classification.2',
            'label' => 'Classification 3',
            'fieldWidth' => 5
        ),
        array(
            'default' => '',
            'name' => 'next_contact',
            'label' => 'Next contact date',
            'fieldWidth' => 10
        ),
        array(
            'default' => '',
            'name' => 'membership_number',
            'label' => 'Membership Number',
            'fieldWidth' => 20
        ),
        array(
            'default' => '',
            'name' => 'password',
            'label' => 'Password',
            'fieldWidth' => 25
        ),
        array(
            'default' => 'WEB',
            'name' => 'originator_software',
            'label' => 'Originator software',
            'fieldWidth' => 10
        ),
        array(
            'default' => '',
            'name' => 'default_discount_reason',
            'label' => 'Default discount reason',
            'fieldWidth' => 5
        ),
        array(
            'default' => '',
            'name' => 'DPID',
            'label' => 'DPID',
            'fieldWidth' => 8
        ),
        array(
            'default' => '',
            'name' => 'DPID_barcode',
            'label' => 'DPID Barcode',
            'fieldWidth' => 37
        ),
        array(
            'default' => '',
            'name' => 'diary_active_flag',
            'label' => 'Diary Active Flag',
            'fieldWidth' => 1
        ),
        array(
            'default' => '',
            'name' => 'external_id',
            'label' => 'External ID',
            'fieldWidth' => 20
        ),
        array(
            'default' => '',
            'name' => 'customised_info',
            'label' => 'Customised Info',
            'fieldWidth' => 50
        ),
        array(
            'default' => '!',
            'name' => 'control_character',
            'label' => 'Control character',
            'fieldWidth' => 1
        ),
    );

    public function getDataArray()
    {
        $_data = array();

        $this->agent = $this->limitString( $this->_helper->getHarmonyWebAgent(), 5 );
        $this->debtor_namekey = $this->limitString( $this->_helper->getHarmonyDebtorNamekey(), 10);
        $this->user_id = $this->limitString( $this->_helper->getHarmonyUserId(), 10);
        $this->terminal_id = $this->limitString( $this->_helper->getHarmonyTerminalId(), 10);

        foreach (self::$fieldProperties as $field) {
            $fieldName = $field['name'];
            if (property_exists($this, $fieldName)) {
                $_data[$field['name']] = $this->{$field['name']};
            } elseif (isset($field['default'])) {
                $_data[$fieldName] = $field['default'];
            } else {
                throw new IOValidationErrorException(sprintf("No value specified for required field (%s)", $fieldName));
            }
        }
        return $_data;
    }

    public static function castInt($value)
    {
        return (int)$value;
    }

    public static function generateNamekey($id){
        $prefix = Mage::helper('aligent_emarsys')->getHarmonyNamekeyPrefix();
        $namekey = $prefix . str_pad($id, 10 - strlen($prefix),'0',STR_PAD_LEFT);
        return $namekey;
    }
}
