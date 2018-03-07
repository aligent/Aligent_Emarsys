<?php
$pathInfo = getcwd() . DIRECTORY_SEPARATOR . dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR;
require_once $pathInfo . 'abstract.php';
require_once $pathInfo . 'abstract_shell.php';

class Aligent_Emarsys_Sync_Emarsys_Subscription_Status extends Aligent_Emarsys_Abstract_Shell{
    protected $_newsletterTable;
    protected $_emailField;

    public function __construct(){
        parent::__construct();
        $this->_newsletterTable = $this->getTableName("newsletter/subscriber");
    }

    public function run(){
        $emUser = $this->getHelper()->getEmarsysAPIUser();
        $emPass = $this->getHelper()->getEmarsysAPISecret();
        $statusField = $this->getHelper()->getEmarsysAPISubscriptionField();

        $client = Mage::helper('aligent_emarsys/emarsys')->getClient($emUser, $emPass);
        $this->_emailField = $client->getFieldId('email');

        $query = $this->getReader()->select()->from($this->_newsletterTable)
            ->reset((Varien_Db_Select::COLUMNS))
            ->columns(['subscriber_email'])
            ->group('subscriber_email')->where('subscriber_email is not null')->query();
        $emails = array();
        $total = $query->rowCount();
        $i = 0;
        while($row = $query->fetchObject()){
            $i++;
            $this->console("\033[8D");
            $this->console(str_pad(round(($i/ $total) * 100, 2) . '%',6));
            if(!in_array($row->subscriber_email, $emails)){
                $emails[] = $row->subscriber_email;
            }

            if(sizeof($emails) >= $this->getHelper()->getEmarsysChunkSize() ){
                $this->processEmails($client, $emails, $statusField);
                $emails = array();
            }
        }
        $this->processEmails($client, $emails, $statusField);
    }

    protected function processEmails($client, $emails, $statusField){
        $result = $client->getContactData(array("keyId" => $this->_emailField,"keyValues" => $emails));

        foreach($result->getData()['result'] as $item){
            $row = new Aligent_Emarsys_Model_EmarsysRecord($client, $item);
            $status = $row->getSubscriptionStatus();

            if($status !== Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE){
                $email = $this->getWriter()->quote($row->getEmail());
                $data = [
                    'subscriber_status' => $row->getSubscriptionStatus()
                ];

                $this->getWriter()->update($this->_newsletterTable, $data, "subscriber_email=$email");
            }
        }
    }
}

$shell = new Aligent_Emarsys_Sync_Emarsys_Subscription_Status();
$shell->run();