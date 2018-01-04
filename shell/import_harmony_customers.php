<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 10/30/17
 * Time: 8:45 AM
 */
require_once 'abstract.php';

class Aligent_Emarsys_Shell_Import_Harmony_Customers extends Mage_Shell_Abstract {
    protected $_argname = array();
    /** @var  $_lwHelper Aligent_Emarsys_Helper_LightweightDataHelper */
    protected $_lwHelper;
    protected $_aligentTable;
    protected $_customerTable;
    protected $_newsletterTable;

    /**
     * @return Aligent_Emarsys_Helper_LightweightDataHelper
     */
    public function getLightWeightHelper(){
        if($this->_lwHelper===null){
            $this->_lwHelper = Mage::helper('aligent_emarsys/lightweightDataHelper');
        }
        return $this->_lwHelper;
    }

    /**
     * Get the database reader connection
     * @return Varien_Db_Adapter_Interface
     */
    public function getReader(){
        return $this->getLightWeightHelper()->getReader();
    }

    public function getWriter(){
        return $this->getLightWeightHelper()->getWriter();
    }


    public function __construct() {
        parent::__construct();
        set_time_limit(0);
        $this->_aligentTable = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->getResource()->getMainTable();
        $this->_newsletterTable = Mage::getModel('newsletter/subscriber')->getResource()->getMainTable();
        $this->_customerTable = Mage::getModel('customer/customer')->getResource()->getEntityTable();
        if($this->getArg('file')) {
            $this->_file = $this->getArg('file');
        }

        $this->_store = $this->getArg('store');
        $this->validateArguments();
    }

    protected function quote($value){
        return $this->getReader()->quote($value);
    }

    protected function abstractLoadFromTableQuery($table, $field,$value){
        return $this->getReader()->select()->from($table)->where($field . '=' . $this->quote($value));
    }

    protected function loadAligentSyncByField($field, $value){
        return $this->abstractLoadFromTableQuery($this->_aligentTable, $field, $value)->query()->fetch();
    }

    protected function loadCustomerByField($field, $value){
        return $this->abstractLoadFromTableQuery($this->_customerTable, $field, $value)->where('store_id=' . $this->quote($this->_store))->query()->fetch();
    }

    protected function loadNewsletterByEmail($field, $value){
        return $this->abstractLoadFromTableQuery($this->_newsletterTable, $field, $value)->where('store_id=' . $this->quote($this->_store))->query()->fetch();
    }

    protected function importCSVRow($row){
        $helper = $this->getHelper();
        $SyncUp = $this->loadAligentSyncByField('harmony_id', $row['Namekey']);
        if(!$SyncUp){
            $Customer = $this->loadCustomerByField('email', $row['Email']);
            if($Customer){
                $SyncUp = $this->getHelper()->findCustomerSyncRecord($Customer['entity_id']);
                $SyncUp->setHarmonyId($row['Namekey']);
                $SyncUp->setHarmonySyncDirty(false);
                $SyncUp->save();
                return true;
            }

            $Newsletter = $this->loadNewsletterByEmail('subscriber_email', $row['Email']);
            if(!$Newsletter){
                $this->getWriter()->insert($this->_newsletterTable, [
                    'subscriber_email'=>$row['Email'],
                    'store_id'=>$this->_store,
                    'subscriber_status'=>Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE]);
                $Newsletter = $this->loadNewsletterByEmail('subscriber_email', $row['Email']);
            }

            $SyncUp = $this->loadAligentSyncByField('email', $row['Email']);
            $syncData = [
                'first_name'=>$row['First Name'],
                'last_name'=>$row['Last Name'],
                'email'=>$row['Email'],
                'dob'=>$row['Date of Birth'],
                'harmony_id'=>$row['Namekey'],
                'harmony_sync_dirty'=>false,
                'newsletter_subscriber_id'=>$Newsletter['subscriber_id'],
                'updated_at'=> Mage::getModel('core/date')->date('Y-m-d H:i:s')
            ];

            if($SyncUp){
                $this->getWriter()->update($this->_aligentTable, $syncData, 'id=' . $SyncUp['id']);
            }else{
                $this->getWriter()->insert($this->_aligentTable, $syncData);
            }
            return true;
        }else{
            $syncData = [
                'first_name'=>$row['First Name'],
                'last_name'=>$row['Last Name'],
                'email'=>$row['Email'],
                'dob'=>$row['Date of Birth'],
                'harmony_id'=>$row['Namekey'],
                'harmony_sync_dirty'=>false,
                'updated_at'=> Mage::getModel('core/date')->date('Y-m-d H:i:s')
            ];
            $this->getWriter()->update($this->_aligentTable, $syncData, 'id=' . $SyncUp['id']);

            return true;
        }
    }

    /**
     * @return Aligent_Emarsys_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function getHelper(){
        return Mage::helper('aligent_emarsys');
    }

    // Shell script point of entry
    public function run() {

        Mage::register('emarsys_newsletter_ignore', true);
        echo "Import customers into: " . $this->_storeObject->getName() . "\n";

        $stream = fopen($this->_file, "r");
        $reader = new Aligent_Emarsys_Model_HarmonyDiaryReader($stream);
        if(!$reader->validateFile()){
            fclose($stream);
            die("This file is not a valid Harmony CSV\n");
        }
        $imported = 0;
        $errors = 0;
        $count = 0;

        while(!feof($stream)){
            $count++;
            $this->getHelper()->log("Processed row $count\n",2);
            $row = $reader->readLine();
            if($row['Email']=='') continue;

            if ($this->importCSVRow($row)) {
                $imported++;
            } else {
                $errors++;
                Mage::log("Unable to import: ", null, 'aligent_emarsys');
                Mage::log(print_r($row, true), null, "aligent_emarsys");
            }
        }
        fclose($stream);

        echo "\nImported $imported, skipped $errors\n";
        Mage::unregister('emarsys_newsletter_ignore');
    }

    protected function validateArguments(){
        if(!$this->_file){
            die("Please specify filename\n");
        }

        if(!file_exists($this->_file)){
            die("Invalid filename " . $this->_file . "\n");
        }

        if(empty($this->_store)){
            die("Please specify store\n");
        }

        $this->_storeObject = Mage::getModel('core/store')->load($this->_store);

        if(!$this->_storeObject->getId()){
            die("Invalid store id\n");
        }
    }

    // Usage instructions
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f import_harmony_customers.php -- [options]
 
  --file <file_name>       The filename to import
  --store <store_id>       The store to import into
 
  help                   This help
 
USAGE;
    }
}
// Instantiate
$shell = new Aligent_Emarsys_Shell_Import_Harmony_Customers();

// Initiate script
$shell->run();