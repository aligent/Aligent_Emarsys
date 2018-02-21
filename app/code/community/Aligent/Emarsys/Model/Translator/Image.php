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

        $vThumbnail = $aRow['thumbnail']!=NULL ? $aRow['thumbnail'] : $aRow['thumbnail_dft'];
        $vThumbnail = ltrim($vThumbnail, "\/");
        $vThumbnail = $oStore->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $vThumbnail;

        return $vThumbnail;
    }
}
