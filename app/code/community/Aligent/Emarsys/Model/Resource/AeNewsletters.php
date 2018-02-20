<?php

class Aligent_Emarsys_Model_Resource_AeNewsletters extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('aligent_emarsys/aeNewsletters', 'subscriber_id');
    }
}