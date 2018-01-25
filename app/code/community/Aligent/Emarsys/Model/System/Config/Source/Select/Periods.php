<?php

class Aligent_Emarsys_Model_System_Config_Source_Select_Periods
{
    public function toOptionArray()
    {
        $data = array(
            array('value' => '1','label' => '1'),
            array('value' => '2','label' => '2'),
            array('value' => '4','label' => '4'),
            array('value' => '8','label' => '8'),
            array('value' => '12','label' => '12'),
            array('value' => '24','label' => '24')
        );

        // Add in days up to a week
        for($i=2; $i <= 7; $i++){
            $data[] = array('value'=>($i * 24), 'label'=>($i * 24));
        }

        return $data;
    }
}