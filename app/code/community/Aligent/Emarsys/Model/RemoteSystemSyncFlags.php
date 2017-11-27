<?php

class Aligent_Emarsys_Model_RemoteSystemSyncFlags extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('aligent_emarsys/remoteSystemSyncFlags');
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();
        if($this->isObjectNew()){
            $this->setCreatedAt( Mage::getModel('core/date')->date('Y-m-d H:i:s') );
        }
        $this->setUpdatedAt( Mage::getModel('core/date')->date('Y-m-d H:i:s') );
        return $this;
    }
}