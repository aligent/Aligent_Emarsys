<?php

class Aligent_Emarsys_Model_Translator_Image
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
        return $this->getRowImage('thumbnail', $aRow, $oStore);
    }

    public function translateZoom($aRow, $vField, $oStore){
        return $this->getRowImage('small_image', $aRow, $oStore);
    }

    protected function getRowImage($field, $aRow, $oStore){
        $vImage = $aRow[$field]!==NULL ? $aRow[$field] : $aRow[$field . '_dft'];
        $vImage = trim($vImage, "\/");
        if($vImage !== '') {
            $vImage = $oStore->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product/' . $vImage;
        }
        return $vImage;

    }
}
