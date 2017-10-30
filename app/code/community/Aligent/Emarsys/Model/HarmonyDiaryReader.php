<?php
class Aligent_Emarsys_Model_HarmonyDiaryReader extends \Aligent\IO\IOCSVFileParser
{

    protected $_headers = array();
    protected $_expectedHeaders = [
        'Namekey',
        'Surname',
        'First Name',
        'Email',
        'Billing Address 1',
        'Billing Address 2',
        'Billing Address 3',
        'Billing Address 4',
        'Billing Postcode',
        'Shipping Address 1',
        'Shipping Address 2',
        'Shipping Address 3',
        'Shipping Address 4',
        'Shipping Postcode',
        'Phno Home',
        'Phno Mobile',
        'Phno Work',
        'Title',
        'Discount Code',
        'Class 1',
        'Class 2',
        'Class 3',
        'Date of Birth',
        'First Contact',
        'Last Contact',
        'Total Sales'
    ];

    public function __construct($handle){
        parent::__construct($handle);
        $this->_headers = parent::readLine();
    }

    public function validateFile(){
        $diff = array_diff_key($this->_headers, $this->_expectedHeaders);
        return (sizeof($diff)==0);
    }

    public function readLine() {
        $lineData = parent::readLine();
        $rowData = array();
        foreach($this->_headers as $index => $key){
            $rowData[$key] = $lineData[$index];
        }
        return $rowData;
    }

}