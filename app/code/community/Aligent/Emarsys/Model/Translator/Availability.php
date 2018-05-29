<?php
class Aligent_Emarsys_Model_Translator_Availability
{
    public function translate($aRow) {
        $avail = isset($aRow['availability']) ? $aRow['availability'] == Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK : false;
        $parentAvail = isset($aRow['ps_availability']) ? $aRow['ps_availability'] == Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK : $avail;

        $status = isset($aRow['status']) && $aRow['status']!==null ? $aRow['status'] : $aRow['status_dft'];
        $status = ($status == Mage_Catalog_Model_Product_Status::STATUS_ENABLED);

        $stock = isset($aRow['stock_qty']) ? $aRow['stock_qty'] : false;
        $parentStock = isset($aRow['ps_stock_qty']) ? $aRow['ps_stock_qty'] : $stock;

        $stock = ($aRow['type_id']!=='simple') ? $stock : $parentStock;
        $avail = ($aRow['type_id']!=='simple') ? $avail : $parentAvail;

        return ($status && $avail && $stock > 0) ? 'TRUE' : 'FALSE';
    }
}