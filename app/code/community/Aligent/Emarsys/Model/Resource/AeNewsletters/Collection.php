<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 9/14/17
 * Time: 3:26 PM
 */
class Aligent_Emarsys_Model_Resource_AeNewsletters_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('aligent_emarsys/aeNewsletters');
    }
}