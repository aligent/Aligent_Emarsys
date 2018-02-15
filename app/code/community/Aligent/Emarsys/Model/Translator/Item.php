<?php

class Aligent_Emarsys_Model_Translator_Item
{

    /**
     * @param $aRow
     * @param $vField
     * @param $oStore Mage_Core_Model_Store
     *
     * @return string
     */
    public function translate($aRow, $vField, $oStore)
    {
        $aRow['item'] = $oStore->getCode() . "_" . $aRow['sku'];
        return $aRow['item'];
    }
}