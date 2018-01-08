<?php

/**
 * Applies filters to the feed data
 */
class Aligent_Emarsys_Model_Filter {
    /**
     * This before query filter will:
     *  - Join the stock_status table so that we can access the qty and availability
     *  - Prevent Simple items from being output - these will be handled by exportSimpleChildren
     *
     * Also note as this uses stock, we will need to ensure that the reindex before feed generation option is switched on
     *
     * @param Varien_Db_Select $oSelect The current select query
     * @param Mage_Core_Model_Store $oStore Sore for which this feed is being generated
     * @param array $aParams An array of parameters defined in the config.xml
     */
    public function beforeQueryFilter(Varien_Db_Select &$oSelect, $oStore, $aParms) {
        /** @var Aligent_Emarsys_Helper_Data $helper */
        $helper = Mage::helper('aligent_emarsys');

        $vStockStatus = Mage::getModel('core/resource_setup', 'core_setup')->getTable('cataloginventory/stock_status');

        $stockFromSimple = $helper->getGetStockFromSimpleProduct();
        $includeSimpleParents = $helper->getIncludeSimpleParents();
        $includeDisabled = $helper->getIncludeDisabled();

        if ($stockFromSimple) {
            // Grab the super link and join on that.
            $vSuperLink = Mage::getModel('core/resource_setup', 'core_setup')->getTable('catalog/product_super_link');
            $oSelect->joinLeft( array( 'sl' => $vSuperLink ), '`main_table`.`entity_id`=`sl`.`parent_id`')
                ->joinLeft( array( 'ss' => $vStockStatus ),
                '`main_table`.`entity_id`=`ss`.`product_id`',
                array(
                    'stock_qty' => 'sum(`ss`.`qty`)',
                    'availability' => 'sum(`ss`.`stock_status`)'
                )
            )->group('COALESCE(`sl`.`parent_id`, `main_table`.`entity_id`)');
        } else {
            $oSelect->joinLeft( array( 'ss' => $vStockStatus ),
                'main_table.entity_id=ss.product_id',
                array(
                    'stock_qty' => 'ss.qty',
                    'availability' => 'ss.stock_status'
                )
            );
        }

        if(!isset($aParms['include_all_stores'])){
            $oSelect->where('ss.website_id=?', array($oStore->getWebsiteId()));
        }

        if ($includeSimpleParents) {
            // Do not include simples, unless they have no parent.
            $oSelect->where('`main_table`.`type_id` <> ? OR (`main_table`.`type_id` = ? AND `sl`.`parent_id` IS NULL)', 'simple');
        } else {
            $oSelect->where('`main_table`.`type_id` <> ?', 'simple');
        }

        if (!$includeDisabled) {
            $oSelect->where('`main_table`.`status` = ?', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        }

        $vSql = (string) $oSelect;

        Mage::getSingleton('aligent_feeds/log')->log("Catalog Select is: $vSql");
    }

}