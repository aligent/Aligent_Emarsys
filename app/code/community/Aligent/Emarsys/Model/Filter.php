<?php

/**
 * Applies filters to the feed data
 */
class Aligent_Emarsys_Model_Filter {
    protected static $_productModel = null;
    protected static $_attributes = array();

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

        $attrs = array('url_path','thumbnail', 'url_key','name','image_label','small_image','small_image_label','price','msrp','special_price');

        $oProductModel = $this->getProductModel();
        $oCollection = $oProductModel->getCollection();

        $oSelect = $oCollection->getSelect();

        foreach($attrs as $attr){
            $attrData = $this->getAttribute($attr);
            $vTable = "tbl_$attr";
            $vDftTable = "tblDft_$attr";
            $this->addAttributeJoin($oSelect, $attr, $attrData['id'], $attrData['table'], $vTable, $storeId);
            $this->addAttributeJoin($oSelect, $attr . '_dft', $attrData['id'], $attrData['table'], $vDftTable, 0);
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

    protected function getAttribute($attrName){
        if(isset(self::$_attributes[$attrName])) return self::$_attributes[$attrName];
        $oProductModel = $this->getProductModel();

        $objAttr = Mage::getSingleton('eav/config')->getCollectionAttribute($oProductModel->getResource()->getType(), $attrName);
        $data = array(
            'table' => $objAttr->getBackendTable(),
            'id' => $objAttr->getId()
        );
        self::$_attributes[$attrName] = $data;

        $objAttr = null;
        $oProductModel = null;
        return $data;
    }

    protected function addAttributeJoin(&$oSelect, $attrName, $attrId, $tableName, $tableAlias, $storeId){
        $oSelect->joinLeft(
            array( $tableAlias => $tableName),
            "e.entity_id = $tableAlias.entity_id and $tableAlias.store_id = $storeId and $tableAlias.attribute_id=" . $attrId,
            array($attrName=> "$tableAlias.value")
        );
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    protected function getProductModel(){
        if(self::$_productModel===null) self::$_productModel = Mage::getModel('catalog/product');
        return self::$_productModel;
    }
}