<?php

class Aligent_Emarsys_Model_Translator_Brand
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
        $value = ($aRow['brand'] !== null ? $aRow['brand'] : $aRow['brand_dft']);
        $values = Aligent_Emarsys_Model_Filter::getAttributeOptions('brand', $oStore->getId());
        return isset($values[$value]) ? $values[$value] : null;
    }
}
