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

        $query = $this->getReader()->select()->from($this->_aligentTable)
            ->reset((Varien_Db_Select::COLUMNS))
            ->columns(['email'])
            ->group('email')->query();

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
                'emarsys_id' => $item[$this->_emarsysField]
            ];
            $this->getWriter()->update($this->_aligentTable, $data, "email=$email");
            $newsletters = $this->getReader()->select()->from($this->_newsletterTable)->where("subscriber_email=$email")->query();
            while($row = $newsletters->fetchObject()){
                $harmonyField = $this->getHelper()->getHarmonyIdField($row->store_id);
                $this->getHelper()->log("Setting Harmony ID from $harmonyField to value " . $item[$harmonyField] . " for store " . $row->store_id, 2);
                $this->getWriter()->update($this->_aligentTable, ['harmony_id' => $item[$harmonyField]], "newsletter_subscriber_id=" . $row->subscriber_id);
            }
        }
    }

}

$shell = new Aligent_Emarsys_Shell_Sync_Emarsys_Ids();
$shell->run();