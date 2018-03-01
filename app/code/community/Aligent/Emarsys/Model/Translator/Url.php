<?php

/**
 * Singleton class to return product URL.
 * Since Magento 1.13.1 url_path is no longer used. In which case Product Url is determined using unique url_key and url suffix (.html)
 * The Aligent translator will use the 'url_path' value as the first option, however if this is not correctly set
 * we need to force the translator to use url_key.
 */
class Aligent_Emarsys_Model_Translator_Url{
    public function translate($aRow, $vField, $oStore) {
        $vUrl = $oStore->getBaseUrl();
        $urlKey = ($aRow['url_key'] !== null) ? $aRow['url_key'] : $aRow['url_key_dft'];
        $urlKey = trim($urlKey);

        if($urlKey == '') return $urlKey;

        $vUrl .= $urlKey;

        // only add the UrlSuffix when it exists
        if (Mage::helper('catalog/product')->getProductUrlSuffix()) $vUrl .= '.'.Mage::helper('catalog/product')->getProductUrlSuffix();

        return $vUrl;
    }
}