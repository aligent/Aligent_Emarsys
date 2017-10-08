<?php

class Aligent_Emarsys_Model_System_Config_Source_Select_Emarsysfields
{
    public function toOptionArray()
    {
        $configHelper = Mage::helper('aligent_emarsys');
        $helper = Mage::helper('aligent_emarsys/emarsys');
        try {
            $data = array();
            $data[] = array('value' => '-1', 'label'=>'-- Please select -- ');

            $storeId = $this->getCurrentStoreScope();
            $emUser = $configHelper->getEmarsysAPIUser($storeId);
            $emPass = $configHelper->getEmarsysAPISecret($storeId);
            $fields = $helper->getClient($emUser, $emPass)->getFields();

            foreach($fields->getData() as $field){
                $data[] = array('value'=> $field['id'], 'label'=>$field['name']);
            }
            return $data;
        }catch(\Exception $e){
            return array(array('value' => '-1', 'label'=>'(Check API credentials)'));
        }
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
}