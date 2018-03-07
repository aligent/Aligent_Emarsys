<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 9/15/17
 * Time: 11:06 AM
 */

class Aligent_Emarsys_Model_HarmonyDiary
{
    const HARMONY_DEFAULT_EXISTING = '~';

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
        if($dateValue === null || $dateValue === '' || $dateValue === '0000-00-00') return '';

        if(is_string($dateValue) ){
            $dateValue = trim($dateValue);
            if(strpos($dateValue, ' ') > 0){
                $dateValue = substr($dateValue, 0, strpos($dateValue, ' '));
            }
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

    protected function mapAddressModel($addressId){
        $address = Mage::getModel('customer/address')->load($addressId);
        $address->getData();
        $data = array();
        $data['address_1'] = $this->limitString( $address->getStreet(1), 30 );
        $data['address_2'] = $this->limitString( $address->getStreet(2), 25 );
        $data['address_city'] = $address->getCity();
        $data['address_region']=$address->getRegionCode();
        $data['address_country'] = $this->limitString( $address->getCountryModel()->getName(), 20 );
        $data['address_postcode'] =$this->limitString( $address->getPostcode(), 10 );

    }

    protected function populateAddress($address, $fieldMap){
        if (!$address) return;
        if(!is_array($address)) $address = $this->mapAddressModel($address);

        $this->{$fieldMap[0]} = $this->limitString( $address['address_1'], 30 );
        $this->{$fieldMap[1]} = $this->limitString( $address['address_2'], 25 );
        $this->{$fieldMap[2]} = $this->limitString( $address['address_city'] . ' ' . $address['address_region'], 25 );
        $this->{$fieldMap[3]} = $this->limitString( $address['address_country'], 20 );
        $this->{$fieldMap[4]} = $this->limitString( $address['address_postcode'], 10 );
    }

    public function fillMagentoAddress($addressData, $type){
        $streetMethod = "get{$type}Street";
        $streets = preg_split("/\n/", $addressData->$streetMethod());
        if(sizeof($streets) < 2) $streets[] = ''; // ensure there will always be a line two, even if its blank.

        $cityMethod = "get{$type}City";
        $regionMethod = "get{$type}Region";
        $countryMethod = "get{$type}Country";
        $postcodeMethod= "get{$type}Postcode";

        $data = array(
            'address_1' => $streets[0],
            'address_2'=> $streets[1],
            'address_city' => $addressData->$cityMethod(),
            'address_region' => $addressData->$regionMethod(),
            'address_country' => $addressData->$countryMethod(),
            'address_postcode' => $addressData->$postcodeMethod()
        );
        return $data;
    }

    public function fillMagentoBillingAddress($addressData){
        $this->populateAddress( $this->fillMagentoAddress($addressData, 'Billing'), array('address_1','address_2','address_3','address_4','postcode'));
    }

    public function fillMagentoShippingAddress($addressData){
        $this->populateAddress( $this->fillMagentoAddress($addressData, 'Shipping'), array('deilvery_address_1','deilvery_address_2','deilvery_address_3','deilvery_address_4','deilvery_postcode'));
    }

    protected function ensureSyncData($customer){
        return $this->_helper->ensureCustomerSyncRecord($customer->getId());
    }

    public function fillMagentoSubscriber($subscriber){
        $localSyncData = $this->_helper->ensureNewsletterSyncRecord($subscriber->getId());

        $this->action = ($localSyncData->getHarmonyId()) ? 'M' : 'A';
        $this->name_1 = $this->limitString( $localSyncData->getFirstName(), 30) ;
        $this->name_2 = $this->limitString( $localSyncData->getLastName(), 30);
        $this->email = $this->limitString($subscriber->getEmail(), 60);

        $this->date_of_birth = $this->harmonyDate( $localSyncData->getDob());

        switch($subscriber->getSubscriberStatus()){
            case Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED:
                $emailStatus = 'EMAIL';
                break;
            case Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE:
                $emailStatus = '';
                break;
            default:
                $emailStatus = 'NOEML';
        }

        $this->{'classification.1'} = $emailStatus;
        $this->namekey = Aligent_Emarsys_Model_HarmonyDiary::generateNamekey($localSyncData->getId());
    }

    public function getCustomerSubscribed($customer){
        $subscriber = $this->_helper->getCustomerSubscriber($customer);
        switch($subscriber->getSubscriberStatus()){
            case Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED:
                $emailStatus = 'EMAIL';
                break;
            case Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE:
                $emailStatus = '';
                break;
            default:
                $emailStatus = 'NOEML';
        }
        return $emailStatus;
    }

    public function fillMagentoCustomerFromData($customer, $localSyncId, $localSyncHarmonyId){
        //$localSyncData = $this->ensureSyncData($customer);

        $this->action = ($localSyncHarmonyId) ? 'M' : 'A';
        $this->name_1 = $this->limitString( $customer->getLastname(), 30 );
        $this->name_2 = $this->limitString( $customer->getFirstname(), 30);
        $this->email = $this->limitString($customer->getEmail(), 60);
        $this->{'telephone.0'} = $this->limitString( $customer->getTelephone(), 20);

        $this->fillMagentoBillingAddress($customer);
        $this->fillMagentoShippingAddress($customer);

        $this->date_of_birth = $this->harmonyDate( $customer->getDob() );
        $this->{'classification.1'} = $this->getCustomerSubscribed($customer);

        $this->namekey = Aligent_Emarsys_Model_HarmonyDiary::generateNamekey($localSyncId);
    }

    public function fillMagentoCustomer($customer)
    {
        $localSyncData = $this->ensureSyncData($customer);

        $this->action = ($localSyncData->getHarmonyId()) ? 'M' : 'A';
        $this->name_1 = $this->limitString( $customer->getLastname(), 30 );
        $this->name_2 = $this->limitString( $customer->getFirstname(), 30);
        $this->email = $this->limitString($customer->getEmail(), 60);
        $this->{'telephone.0'} = $this->limitString( $customer->getTelephone(), 20);

        $this->fillMagentoBillingAddress($customer);
        $this->fillMagentoShippingAddress($customer);

        $this->date_of_birth = $this->harmonyDate( $customer->getDob() );
        $this->{'classification.1'} = $this->getCustomerSubscribed($customer);

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
            'default' => self::HARMONY_DEFAULT_EXISTING,
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
            'default' => self::HARMONY_DEFAULT_EXISTING,
        ),
        array(
            'name' => 'name_2',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'First Name',
            'fieldWidth' => 30
        ),
        /* Billing Address */
        array(
            'name' => 'address_1',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Billing Address',
            'fieldWidth' => 30
        ),
        array(
            'name' => 'address_2',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Billing Address 2 ',
            'fieldWidth' => 25
        ),
        array(
            'name' => 'address_3',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Billing Address 3',
            'fieldWidth' => 25
        ),
        array(
            'name' => 'address_4',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Billing Address 4',
            'fieldWidth' => 20
        ),
        array(
            'name' => 'postcode',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Billing Postcode',
            'fieldWidth' => 10
        ),
        /* Shipping Address */
        array(
            'name' => 'delivery_address_1',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Shipping Address',
            'fieldWidth' => 30
        ),
        array(
            'name' => 'delivery_address_2',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Shipping Address 2 ',
            'fieldWidth' => 25
        ),
        array(
            'name' => 'delivery_address_3',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Shipping Address 3',
            'fieldWidth' => 25
        ),
        array(
            'name' => 'delivery_address_4',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Shipping Address 4',
            'fieldWidth' => 20
        ),
        array(
            'name' => 'delivery_postcode',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Shipping Postcode',
            'fieldWidth' => 10
        ),
        array(
            'name' => 'agent',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Agent',
            'fieldWidth' => 5,
            'writeProcessor' => array('Aligent_Emarsys_Model_HarmonyDiary', 'castInt')
        ),
        array(
            'name' => 'telephone.0',
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'label' => 'Telephone (home)',
            'fieldWidth' => 20
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'telephone.1',
            'label' => 'Mobile',
            'fieldWidth' => 20
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'telephone.3',
            'label' => 'Telephone (work)',
            'fieldWidth' => 20
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'prospect',
            'label' => 'Prospect',
            'fieldWidth' => 1
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'fax',
            'label' => 'Fax Number',
            'fieldWidth' => 20
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'text_1',
            'label' => 'Text 1',
            'fieldWidth' => 30
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'text_2',
            'label' => 'Text 2',
            'fieldWidth' => 30
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'email',
            'label' => 'E-mail',
            'fieldWidth' => 60
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'title',
            'label' => 'Title',
            'fieldWidth' => 7
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'date_of_birth',
            'label' => 'Date of birth',
            'fieldWidth' => 10
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
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
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'category',
            'label' => 'Category',
            'fieldWidth' => 5
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'classification.0',
            'label' => 'Classification 1',
            'fieldWidth' => 5
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'classification.1',
            'label' => 'Classification 2',
            'fieldWidth' => 5
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'classification.2',
            'label' => 'Classification 3',
            'fieldWidth' => 5
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'next_contact',
            'label' => 'Next contact date',
            'fieldWidth' => 10
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'membership_number',
            'label' => 'Membership Number',
            'fieldWidth' => 20
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
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
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'default_discount_reason',
            'label' => 'Default discount reason',
            'fieldWidth' => 5
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'DPID',
            'label' => 'DPID',
            'fieldWidth' => 8
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'DPID_barcode',
            'label' => 'DPID Barcode',
            'fieldWidth' => 37
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'diary_active_flag',
            'label' => 'Diary Active Flag',
            'fieldWidth' => 1
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
            'name' => 'external_id',
            'label' => 'External ID',
            'fieldWidth' => 20
        ),
        array(
            'default' => self::HARMONY_DEFAULT_EXISTING,
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
        $syncRecord = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($id);
        if(!$syncRecord->getId()) throw new Exception("Invalid sync record ID");
        if( $syncRecord->getHarmonyId() !== null && $syncRecord->getHarmonyId() !== 0 ){
            $namekey = $syncRecord->getHarmonyId();
        }else {
            $prefix = Mage::helper('aligent_emarsys')->getHarmonyNamekeyPrefix();
            $namekey = $prefix . str_pad($id, 10 - strlen($prefix), '0', STR_PAD_LEFT);
        }
        return $namekey;
    }
}
