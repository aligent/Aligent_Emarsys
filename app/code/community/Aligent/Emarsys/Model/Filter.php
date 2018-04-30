<?php

/**
 * Applies filters to the feed data
 */
class Aligent_Emarsys_Model_Filter {
    protected static $_productModel = null;
    protected static $_attributes = array();
    protected static $_attributeValues = array();

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

        $attrs = array('url_path','thumbnail', 'url_key','name','image_label','small_image','small_image_label','price','special_price', 'brand');

        $oProductModel = $this->getProductModel();
        $oCollection = $oProductModel->getCollection();

        $oSelect = $oCollection->getSelect();
        $oSelect->columns('sku');

        foreach($attrs as $attr){
            $attrData = $this->getAttribute($attr);
            $this->addAttributeJoin($oSelect, $attrData, $storeId);
            $this->addAttributeJoin($oSelect, $attrData, 0);
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

        $objAttr = Mage::getSingleton('eav/config')->getCollectionAttribute($this->getProductModel()->getResource()->getType(), $attrName);

        $data = array(
            'name' => $attrName,
            'table' => $objAttr->getBackendTable(),
            'frontend_type' => $objAttr->getFrontendInput(),
            'id' => $objAttr->getId()
        );
        self::$_attributes[$attrName] = $data;

        $objAttr = null;
        return $data;
    }

    /**
     * @param $oSelect Varien_Db_Select
     * @param $attrData array
     * @param $storeId int
     */
    protected function addAttributeJoin(&$oSelect, $attrData, $storeId){
        $tableAlias = "tbl" . ($storeId==0 ? 'Dft' : '') . '_' . $attrData['name'];
        $oSelect->joinLeft(
            array( $tableAlias => $attrData['table']),
            "e.entity_id = $tableAlias.entity_id and $tableAlias.store_id = $storeId and $tableAlias.attribute_id=" . $attrData['id'],
            array($attrData['name']  . ($storeId==0 ? '_dft' : '') => "$tableAlias.value")
        );
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    protected function getProductModel(){
        if(self::$_productModel===null) self::$_productModel = Mage::getModel('catalog/product');
        return self::$_productModel;
    }

    public static function getAttributeOptions($attrName, $storeId){
        if(isset(self::$_attributeValues[$attrName]) && isset(self::$_attributeValues[$attrName][$storeId])){
            return self::$_attributeValues[$attrName][$storeId];
        }

        if(!isset(self::$_attributeValues[$attrName])) self::$_attributeValues[$attrName] = array();

        $attribute = Mage::getModel('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY,$attrName);
        $values = array();
        if($attribute->usesSource()) {
            $options = $attribute->getSource()->getAllOptions(false);
            foreach($options as $option) {
                if(count($option)==2) {
                    $values[$option['value']] = $option['label'];
                }
            }
        }
        self::$_attributeValues[$attrName][$storeId] = $values;
        return $values;
    }
}