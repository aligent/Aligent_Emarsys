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

        $query = $this->getReader()->select()->from($this->_aligentTable)
            ->reset((Varien_Db_Select::COLUMNS))
            ->columns(['email'])
            ->group('email')->where('email is not null')->query();

        $emails = array();
        while($row = $query->fetchObject()){
            if(!in_array($row->email, $emails)){
                $emails[] = $row->email;
            }

            if(sizeof($emails) >= $this->getHelper()->getEmarsysChunkSize() ){
                $this->processEmails($client, $emails);
                $emails = array();
            }
        }
        $this->processEmails($client, $emails);
    }

    protected function processEmails($client, $emails){
        $result = $client->getContactData(array("keyId" => $this->_emailField,"keyValues" => $emails));
        echo "Processing chunk of size " . sizeof($result->getData()['result']) . PHP_EOL;

        foreach($result->getData()['result'] as $item){
            $row = new Aligent_Emarsys_Model_EmarsysRecord($client, $item);
            echo "Syncing " . $row->getEmail() . "\n";

            $email = $this->getWriter()->quote($row->getEmail());
            $data = [
                'emarsys_id' => $row->getId(),
                'emarsys_sync_dirty' => 0, // As we have synced with emarsys, we should be clean.
                'harmony_sync_dirty' => 1, // As we have synced with emarsys and may have updated information.
                'updated_at' => Mage::getModel('core/date')->date('Y-m-d H:i:s'), // Timestamps
            ];

            // Sync optional data from Emarsys into Magento only if it is not null.
            if ($this->getHelper()->shouldSyncEmarsysFirstnameField() && $row->getFirstName() !== null) {
                $data['first_name'] = $row->getFirstName();
            }
            if ($this->getHelper()->shouldSyncEmarsysLastnameField() && $row->getLastName() !== null) {
                $data['last_name'] = $row->getLastName();
            }
            if ($this->getHelper()->shouldSyncEmarsysGenderField() && $row->getGender() !== null) {
                $data['gender'] = $row->getGender();
            }
            if ($this->getHelper()->shouldSyncEmarsysDOBField() && $row->getDOB() !== null) {
                $data['dob'] = $row->getDOB();
            }
            $this->getWriter()->update($this->_aligentTable, $data, "email=$email");

            if ($this->getHelper()->shouldSyncEmarsysHarmonyIdField()) {
                // Update the aligent table for all subscribers with this email, to account for the same email used in different store scopes.
                $newsletters = $this->getReader()->select()->from($this->_newsletterTable)->where("subscriber_email=$email")->query();
                while($newsRow = $newsletters->fetchObject()){

                    $harmonyField = $this->getHelper()->getHarmonyIdField($newsRow->store_id);
                    // Do not insert harmony ID of null as this may override what is present.
                    if ($row->getHarmonyId() !== null) {
                        $this->getHelper()->log("Setting Harmony ID from $harmonyField to value " . $row->getHarmonyId() . " for store " . $newsRow->store_id, 2);
                        $this->getWriter()->update($this->_aligentTable, ['harmony_id' => $row->getHarmonyId()], "newsletter_subscriber_id=" . $newsRow->subscriber_id);
                    }
                }
            }
        }
    }

}

$shell = new Aligent_Emarsys_Shell_Sync_Emarsys_Ids();
$shell->run();