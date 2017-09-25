<?php

class Aligent_Emarsys_Model_System_Config_Source_Select_Timeoutvalues
{
    public function toOptionArray()
    {
        return array(
            array('value' => '1','label' => '1'),
            array('value' => '3','label' => '3'),
            array('value' => '5','label' => '5'),
            array('value' => '10','label' => '10'),
            array('value' => '15','label' => '15'),
            array('value' => '20','label' => '20'),
            array('value' => '30','label' => '30'),
            array('value' => '60','label' => '60'),
        );
    }
}