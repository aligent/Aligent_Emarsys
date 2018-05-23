<?php
class Aligent_Emarsys_Model_Translator_Availability
{
    public function translate($aRow) {
        $avail = isset($aRow['availability']) ? $aRow['availability'] : false;
        $parentAvail = isset($aRow['ps_availability']) ? $aRow['ps_availability'] : false;

        $stock = isset($aRow['stock_qty']) ? $aRow['stock_qty'] : false;
        $parentStock = isset($aRow['ps_stock_qty']) ? $aRow['ps_stock_qty'] : false;

        $stock = ($aRow['type_id']!=='simple') ? $stock : $parentStock;
        $avail = ($aRow['type_id']!=='simple') ? $avail : $parentAvail;

        if($avail && $stock > 0) $avail=false;

        return $avail ? 'TRUE' : 'FALSE';
    }
}