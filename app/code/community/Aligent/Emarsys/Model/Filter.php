<?php

/**
 * Applies filters to the feed data
 */
class Aligent_Emarsys_Model_Filter {
    /**
     * @param Varien_Db_Select $oSelect
     * @param $oStore Mage_Core_Model_Store
     * @param $aParms
     */
    public function beforeQueryFilter(Varien_Db_Select &$oSelect, $oStore, $aParms)
    {
        /** @var Aligent_Emarsys_Helper_Data $helper */
        $helper = Mage::helper('aligent_emarsys');

        $websiteIds = array($oStore->getWebsiteId());
        $storeId = $oStore->getStoreId();

        $attrs = array('url_path','name','image_label','small_image','small_image_label','category_id','price','availability','brand_value','msrp','special_price');

        $oProductModel = Mage::getModel('catalog/product');
        $oCollection = $oProductModel->getCollection();

        $oSelect = $oCollection->getSelect();
        $attrUrlKey = Mage::getSingleton('eav/config')->getCollectionAttribute($oProductModel->getResource()->getType(), 'url_key');
        $vUrlKey = $attrUrlKey->getBackendTable();

        $oSelect->joinLeft( array( 'uk' => $vUrlKey), 'e.entity_id=uk.entity_id and uk.store_id=' . $storeId . ' and uk.attribute_id=' . $attrUrlKey->getId(), array('url_key'=> 'uk.value'));
        $oSelect->joinLeft( array( 'ukd' => $vUrlKey), 'e.entity_id=ukd.entity_id and ukd.store_id=0 and ukd.attribute_id=' . $attrUrlKey->getId(), array('url_key_dft'=> 'ukd.value'));

        foreach($attrs as $attr){
            $oCollection->addAttributeToSelect($attr);
            $oCollection->addAttributeToSort($attr);
        }

        $oCollection->addStoreFilter($storeId);
        $oCollection->addWebsiteFilter($websiteIds);

        $oSelect = $oCollection->getSelect();
        $vStockStatus = Mage::getModel('core/resource_setup', 'core_setup')->getTable('cataloginventory/stock_status');
        $vSuperLink = Mage::getModel('core/resource_setup', 'core_setup')->getTable('catalog/product_super_link');

        $oSelect->joinLeft( array( 'sl' => $vSuperLink ), '`e`.`entity_id`=`sl`.`product_id`');
        $oSelect->joinLeft( array( 'pss' => $vStockStatus ),
            '`sl`.parent_id=`pss`.`product_id` AND `pss`.website_id = ' . $oStore->getWebsiteId(),
            array(
                'ps_stock_qty' => 'sum(`pss`.`qty`)',
                'ps_availability' => 'sum(`pss`.`stock_status`)'
            )
        );

        $oSelect->joinLeft( array( 'ss' => $vStockStatus ),
                '`e`.`entity_id`=`ss`.`product_id` AND `ss`.website_id = ' . $oStore->getWebsiteId(),
                array(
                    'stock_qty' => 'sum(`ss`.`qty`)',
                    'availability' => 'sum(`ss`.`stock_status`)'
                )
            )->group('COALESCE(`sl`.`parent_id`, `e`.`entity_id`)');


        // Do not include simples, unless they have no parent.
        $oSelect->where('`e`.`type_id` <> ? OR (`e`.`type_id` = ? AND `sl`.`parent_id` IS NULL)', 'simple');

        $vSql = (string) $oSelect;

        Mage::getSingleton('aligent_feeds/log')->log("Catalog Select is: $vSql");
    }
}