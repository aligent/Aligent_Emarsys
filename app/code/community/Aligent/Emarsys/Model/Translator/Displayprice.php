<?php

class Aligent_Emarsys_Model_Translator_Displayprice
{

    public function translate($aRow, $vField, $oStore)
    {
        return $aRow['price'];
    }

    public function translateCurrentPrice($aRow, $vField, $oStore){
        $price = $aRow['special_price']!==null ? $aRow['special_price'] : $aRow['price'];
        return $price;
    }

    public function translateDisplay($aRow, $vField, $oStore)
    {
        $price = $aRow['special_price']!==null ? $aRow['special_price'] : $aRow['price'];

        return Mage::helper('core')->currencyByStore($price, $oStore, true, false);
    }

}