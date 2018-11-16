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

        if($helper->getIncludeDisabled()){
            $currentStore = Mage::app()->getStore();
            Mage::app()->setCurrentStore('admin');
        }

        $websiteIds = array($oStore->getWebsiteId());
        $storeId = $oStore->getStoreId();

        $oCollection = Mage::getSingleton('catalog/product')->getCollection();
        $oCollection->addStoreFilter($storeId);
        $oCollection->addWebsiteFilter($websiteIds);

        $oSelect = $oCollection->getSelect();
        $vStockStatus = Mage::getModel('core/resource_setup', 'core_setup')->getTable('cataloginventory/stock_status');
        $vSuperLink = Mage::getModel('core/resource_setup', 'core_setup')->getTable('catalog/product_super_link');
        $oSelect->joinLeft( array( 'sl' => $vSuperLink ), '`e`.`entity_id`=`sl`.`parent_id`');
        $oSelect->joinLeft( array( 'slt' => $vSuperLink), 'e.entity_id=slt.product_id');
        $oSelect->joinLeft( array( 'pss' => $vStockStatus ),
            '`sl`.product_id=`pss`.`product_id` AND `pss`.website_id = ' . $oStore->getWebsiteId(),
            array(
                'ps_stock_qty' => 'sum(`pss`.`qty`)',
                'ps_availability' => 'max(`pss`.`stock_status`)'
            )
        )->group('COALESCE(sl.parent_id, e.entity_id)');

        $oSelect->joinLeft( array( 'ss' => $vStockStatus ),
            '`e`.`entity_id`=`ss`.`product_id` AND `ss`.website_id = ' . $oStore->getWebsiteId(),
            array(
                'stock_qty' => 'sum(`ss`.`qty`)',
                'availability' => 'max(`ss`.`stock_status`)'
            )
        );

        $oSelect->where('((type_id = ? and slt.parent_id is null) or (type_id != ?))','simple');

        $oSelect->reset(Varien_Db_Select::COLUMNS);
        $oSelect->columns([
            'sku' => 'sku',
            'type_id' => 'type_id',
            'entity_id' => 'entity_id',
            'ps_stock_qty' => 'sum(ss.qty)',
            'stock_qty' => 'sum(pss.qty)',
            'ps_availability' => 'max(ss.stock_status)',
            'availability' => 'max(pss.stock_status)'
        ]);

        $attrs = array('status','url_path','thumbnail', 'url_key','name','image_label','small_image','small_image_label','price','special_price', 'brand');
        foreach($attrs as $attr){
            $attrData = $this->getAttribute($attr);
            $this->addAttributeJoin($oSelect, $attrData, $oStore->getWebsiteId());
            $this->addAttributeJoin($oSelect, $attrData, 0);
        }

        $vSql = (string) $oSelect;
        if($helper->getIncludeDisabled()){
            Mage::app()->setCurrentStore($currentStore);
        }

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