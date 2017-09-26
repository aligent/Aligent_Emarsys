<?php

class Aligent_Emarsys_Model_System_Config_Source_Select_Emarsysfields
{
    public function toOptionArray()
    {
        $helper = Mage::helper('aligent_emarsys/emarsys');
        try {
            $fields = $helper->getClient()->getFields();
            $data = array();
            $data[] = array('value' => '-1', 'label'=>'-- Please select -- ');

            foreach($fields->getData() as $field){
                $data[] = array('value'=> $field['id'], 'label'=>$field['name']);
            }
            return $data;
        }catch(\Exception $e){
            return array(array('value' => '-1', 'label'=>'(Check API credentials)'));
        }
    }
}