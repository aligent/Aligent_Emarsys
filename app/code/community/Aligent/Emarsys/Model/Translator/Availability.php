<?php
class Aligent_Emarsys_Model_Translator_Availability
{
    public function translate($aRow) {
        $fieldName = ($aRow['type_id']=='simple') ? 'availability' : 'ps_availability';
        $avail = isset($aRow[$fieldName]) ? $aRow[$fieldName] : false;
        return $avail ? 'TRUE' : 'FALSE';
    }
}