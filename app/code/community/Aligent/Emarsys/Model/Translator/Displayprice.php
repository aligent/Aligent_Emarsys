<?php

class Aligent_Emarsys_Model_Translator_Displayprice
{

    public function translateCurrentPrice($aRow, $vField, $oStore){
        $specialPrice = $aRow['special_price'] !== null ? $aRow['special_price'] : $aRow['special_price_dft'];
        $price = $this->getRegularPrice($aRow, $oStore);

        return $specialPrice !== null ? $specialPrice : $price;
    }

    public function translateDisplay($aRow, $vField, $oStore){
        $specialPrice = $aRow['special_price'] !== null ? $aRow['special_price'] : $aRow['special_price_dft'];
        $price = $aRow['price'] !== null ? $aRow['price'] : $aRow['price_dft'];
        $displayPrice = $specialPrice !== null ? $specialPrice : $price;
        return Mage::helper('core')->currencyByStore($displayPrice, $oStore, true, false);
    }

    public function translate($aRow, $vField, $oStore){
        return $this->getRegularPrice($aRow, $oStore);
    }

    protected function getRegularPrice($aRow, $oStore){
        $price = $aRow['price'] !== null ? $aRow['price'] : $aRow['price_dft'];
        if($aRow['type_id'] == 'giftcard'){
            return Mage::helper('aligent_emarsys')->getGiftcardDefaultPrice($oStore);
        }
        return $price;
    }

}