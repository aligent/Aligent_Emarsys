<?php
class Aligent_Emarsys_Model_Translator_Availability
{
    public function translate($aRow) {
        $avail = isset($aRow['availability']) ? $aRow['availability'] : false;
        return $avail ? 'TRUE' : 'FALSE';
    }
}