<?php

class Aligent_Emarsys_Model_Translator_Displayprice
{

    public function translate($aRow, $vField, $oStore)
    {
        return Mage::helper('core')->currency($aRow['price'], true, false);
    }
}