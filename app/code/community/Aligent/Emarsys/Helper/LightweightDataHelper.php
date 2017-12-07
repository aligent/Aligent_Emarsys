<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 9/20/17
 * Time: 9:45 AM
 */
class Aligent_Emarsys_Helper_LightweightDataHelper extends Mage_Core_Helper_Abstract {
    protected $_resource = null;
    protected $_reader = null;
    protected $_writer = null;

    public function save($table, $data, $keyField, $keyValue){
        $writer = self::getWriter();
        $keyValue = $writer->quote( $keyValue );
        $writer->update($table, $data, $keyField . '=' . $keyValue);
    }

    /**
     * Get the database writer connection
     * @return Mage_Core_Model_Resource
     */
    public function getResource(){
        if($this->_resource===null){
            $this->_resource = Mage::getSingleton('core/resource');
        }
        return $this->_resource;
    }

    /**
     * Get the database reader connection
     * @return Varien_Db_Adapter_Interface
     */
    public function getReader(){
        if($this->_reader===null){
            $this->_reader= self::getResource()->getConnection('core_read');
        }
        return $this->_reader;
    }

    /**
     * Get the database writer connection
     * @return Varien_Db_Adapter_Interface
     */
    public function getWriter(){
        if($this->_writer===null){
            $this->_writer= self::getResource()->getConnection('core_read');
        }
        return $this->_writer;
    }
}

