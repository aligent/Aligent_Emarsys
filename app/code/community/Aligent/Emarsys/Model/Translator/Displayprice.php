<?php

class Aligent_Emarsys_Model_Translator_Displayprice
{

    public function translate($aRow, $vField, $oStore)
    {
        $oProduct = Mage::getModel('catalog/product')->load($aRow['entity_id']);
        $price = $oProduct->getFinalPrice();
        return Mage::helper('core')->currency($price, true, false);
    }
}