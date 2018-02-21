<?php

class Aligent_Emarsys_Model_Translator_Categories
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
        $oProduct = Mage::getModel('catalog/product');
        $oProduct->setId($aRow['entity_id']);

        $oResource = $oProduct->getResource();
        $oReader = $oResource->getReadConnection();
        $queryCategories = $oResource->getCategoryCollection($oProduct)->addIsActiveFilter()->getSelect();

        $colCategories = $oReader->query($queryCategories);

        $vSeparator = '|';
        $aPaths = array();
        foreach($colCategories as $oCategory) {
            $aPaths[] = Mage::helper('aligent_feeds')->getCategoryPath($oCategory['entity_id'], $oStore);
        }
        $oProduct = null;
        $colCategories = null;

        return implode($vSeparator, $aPaths);
    }
}
