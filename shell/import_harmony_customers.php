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

    public function __construct() {
        parent::__construct();
        set_time_limit(0);
        if($this->getArg('file')) {
            $this->_file = $this->getArg('file');
        }

        $this->_store = $this->getArg('store');
        $this->validateArguments();
    }

    protected function importCSVRow($row){
        $SyncUp = Mage::getModel("aligent_emarsys/remoteSystemSyncFlags")->load($row['Namekey'], 'harmony_id');
        if(!$SyncUp->getId()){
            $Customer = Mage::getModel("customer/customer")->setStore($this->_storeObject)->loadByEmail($row['Email']);
            if($Customer->getId()){
                $SyncUp = $this->getHelper()->findCustomerSyncRecord($Customer->getId());
                $SyncUp->setHarmonyId($row['Namekey']);
                $SyncUp->save();
                return true;
            }

            $Newsletter = Mage::getModel('newsletter/subscriber')->setStoreId($this->_store)->loadByEmail($row['Email']);
            if(!$Newsletter->getId()){
                $Newsletter->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE);
                $Newsletter->save();
            }
            $SyncUp = $this->getHelper()->findNewsletterSyncRecord($Newsletter);
            $SyncUp->setFirstName($row['First Name']);
            $SyncUp->setLastName($row['Surname']);
            $SyncUp->setDob($row['Date of Birth']);
            $SyncUp->setHarmonyId($row['Namekey']);
            $SyncUp->save();
            return true;
        }else{
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

        while(!feof($stream)){
            $row = $reader->readLine();
            if( $this->importCSVRow($row) ){
                $imported++;
            }else{
                $errors++;
                Mage::log("Unable to import: ",null, 'aligent_emarsys');
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