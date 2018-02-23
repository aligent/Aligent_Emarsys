<?php
class Aligent_Emarsys_Model_Translator_Availability
{
    public function translate($aRow) {
        $avail = isset($aRow['availability']) ? $aRow['availability'] : false;
        $parentAvail = isset($aRow['ps_availability']) ? $aRow['ps_availability'] : false;

        if($aRow['type_id']!=='simple' && !$parentAvail) $avail = false;

        return $avail ? 'TRUE' : 'FALSE';
    }
}