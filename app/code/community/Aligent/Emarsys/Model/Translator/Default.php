<?php

class Aligent_Emarsys_Model_Translator_Default
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
        return $aRow[$vField] !== null ? $aRow[$vField] : $aRow[$vField . '_dft'];
    }
}
