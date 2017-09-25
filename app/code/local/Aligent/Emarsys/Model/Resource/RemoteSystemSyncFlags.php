<?php

class Aligent_Emarsys_Model_Resource_RemoteSystemSyncFlags extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('aligent_emarsys/remoteSystemSyncFlags', 'id');
    }
}