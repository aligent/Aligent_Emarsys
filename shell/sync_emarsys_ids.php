<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 12/21/17
 * Time: 10:50 AM
 */
require_once 'abstract.php';
require_once 'abstract_shell.php';

class Aligent_Emarsys_Shell_Sync_Emarsys_Ids extends Aligent_Emarsys_Abstract_Shell {
    const EMARSYS_EMAIL_CHUNK_SIZE = 50;
    protected $_aligentTable;
    protected $_newsletterTable;
    protected $_harmonyField = null;
    protected $_emarsysField = null;
    protected $_emailField = null;
    protected $_fields = null;
    protected $_emarsysHelper = null;

    public function __construct(){
        parent::__construct();
        $this->_aligentTable = $this->getTableName('aligent_emarsys/remoteSystemSyncFlags');
        $this->_newsletterTable = $this->getTableName("newsletter/subscriber");
    }

    /**
     * @return Aligent_Emarsys_Helper_Emarsys
     */
    protected function getEmarsysHelper(){
        if($this->_emarsysHelper === null){
            $this->_emarsysHelper = Mage::helper('aligent_emarsys/emarsys');
        }
        return $this->_emarsysHelper;
    }

    protected function getCurrentStoreScope(){
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())){
            $store_id = Mage::getModel('core/store')->load($code)->getId();
        } elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) {
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
        }else{
            $store_id = 0;
        }
        return $store_id;
    }

    public function run(){
        $storeId = $this->getCurrentStoreScope();

        $emUser = $this->getHelper()->getEmarsysAPIUser($storeId);
        $emPass = $this->getHelper()->getEmarsysAPISecret($storeId);

        $client = Mage::helper('aligent_emarsys/emarsys')->getClient($emUser, $emPass);
        $this->_emailField = $client->getFieldId('email');
        $this->_harmonyField = $this->getHelper()->getHarmonyIdField();
        $this->_emarsysField = $client->getFieldId('id');

        // We should probably limit this to only get users who do not have a emarsys sync id so they do not run multiple times.
        $query = $this->getReader()->select()->from($this->_aligentTable)
            ->reset((Varien_Db_Select::COLUMNS))
            ->columns(['email'])
            ->group('email')->where('email is not null')->query();

        $emails = array();
        while($row = $query->fetchObject()){
            if(!in_array($row->email, $emails)){
                $emails[] = $row->email;
            }

            if(sizeof($emails) >= self::EMARSYS_EMAIL_CHUNK_SIZE){
                $this->processEmails($client, $emails);
                $emails = array();
            }
        }
        $this->processEmails($client, $emails);
    }

    protected function processEmails($client, $emails){
        $result = $client->getContactData(array("keyId" => $this->_emailField,"keyValues" => $emails));

        foreach($result->getData()['result'] as $item){
            $email = $this->getWriter()->quote($item[$this->_emailField]);
            $data = [
                'emarsys_id' => $item[$this->_emarsysField],
                'emarsys_sync_dirty' => 0, // As we have synced with emarsys, we should be clean.
                'harmony_sync_dirty' => 1, // As we have synced with emarsys and may have updated information.
            ];

            // Sync optional data from Emarsys into Magento only if it is not null.
            if ($this->getHelper()->shouldSyncEmarsysFirstnameField() && $item[$this->getEmarsysHelper()->getFirstnameField()] !== null) {
                $data['first_name'] = $item[$this->getEmarsysHelper()->getFirstnameField()];
            }
            if ($this->getHelper()->shouldSyncEmarsysLastnameField() && $item[$this->getEmarsysHelper()->getLastnameField()] !== null) {
                $data['last_name'] = $item[$this->getEmarsysHelper()->getLastnameField()];
            }
            if ($this->getHelper()->shouldSyncEmarsysGenderField() && $item[$this->getEmarsysHelper()->getGenderField()] !== null) {
                $genders = $this->getEmarsysHelper()->getGenderMap(false);
                $gender = strtolower($item[$this->getEmarsysHelper()->getGenderField()]);

                if (isset($genders[$gender])) {
                    $data['gender'] = $genders[$gender];
                }
            }
            if ($this->getHelper()->shouldSyncEmarsysDOBField() && $item[$this->getEmarsysHelper()->getDobField()] !== null) {
                $data['dob'] = $item[$this->getEmarsysHelper()->getDobField()];
            }

            $this->getWriter()->update($this->_aligentTable, $data, "email=$email");

            if ($this->getHelper()->shouldSyncEmarsysHarmonyIdField()) {
                // Update the aligent table for all subscribers with this email, to account for the same email used in different store scopes.
                $newsletters = $this->getReader()->select()->from($this->_newsletterTable)->where("subscriber_email=$email")->query();
                while($row = $newsletters->fetchObject()){

                    $harmonyField = $this->getHelper()->getHarmonyIdField($row->store_id);
                    // Do not insert harmony ID of null as this may override what is present.
                    if ($item[$harmonyField] !== null) {
                        $this->getHelper()->log("Setting Harmony ID from $harmonyField to value " . $item[$harmonyField] . " for store " . $row->store_id, 2);
                        $this->getWriter()->update($this->_aligentTable, ['harmony_id' => $item[$harmonyField]], "newsletter_subscriber_id=" . $row->subscriber_id);
                    }
                }
            }
        }
    }

}

$shell = new Aligent_Emarsys_Shell_Sync_Emarsys_Ids();
$shell->run();