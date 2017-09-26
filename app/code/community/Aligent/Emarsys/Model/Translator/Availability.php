<?php
class Aligent_Emarsys_Model_Translator_Availability
{
    public function translate($aRow) {
        return $aRow['availability'] ? 'TRUE' : 'FALSE';
    }
}