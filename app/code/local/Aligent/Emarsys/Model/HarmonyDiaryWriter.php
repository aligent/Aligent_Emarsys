<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 9/15/17
 * Time: 10:10 AM
 */
class Aligent_Emarsys_Model_HarmonyDiaryWriter extends Aligent\IO\IOFixedWidthFileWriter
{

    public function __construct($handle){
        parent::__construct($handle);
        parent::initialize(Aligent_Emarsys_Model_HarmonyDiary::$fieldProperties);
    }

}