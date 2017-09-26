<?php
class Aligent_Emarsys_Model_HarmonyDiaryReader extends \Aligent\IO\IOFixedWidthFileParser
{

    public function __construct($handle){
        parent::__construct($handle);
        parent::initialize(Aligent_Emarsys_Model_HarmonyDiary::$fieldProperties);
    }

}