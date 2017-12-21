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
    protected $_harmonyField = null;

    public function __construct(){
        parent::__construct();
        $this->_aligentTable = $this->getTableName('aligent_emarsys/remoteSystemSyncFlags');
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
        $this->_harmonyField = Mage::helper('aligent_emarsys')->getHarmonyIdField();

        $query = $this->getReader()->select()->from($this->_aligentTable)
            ->reset((Varien_Db_Select::COLUMNS))
            ->columns(['email'])
            ->group('email')->query();

        $emails = array();
        while($row = $query->fetchObject()){
            $emails[] = $row->email;
            if(sizeof($emails >= 20)){
                $this->processEmails($client, $emails);
                $emails = array();
            }
        }
        $this->processEmails($client, $emails);
    }

    protected function processEmails($client, $emails){
        $result = $client->getContactData(array("keyId"=>3,"keyValues"=>$emails));
        foreach($result->getData()['result'] as $item){
            $email = $this->getWriter()->quote($item[3]);
            $this->getWriter()->update($this->_aligentTable, ['harmony_id'=>$item[$this->_harmonyField]], "email=$email");
        }
    }

}

$shell = new Aligent_Emarsys_Shell_Sync_Emarsys_Ids();
$shell->run();