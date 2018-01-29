<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 12/21/17
 * Time: 10:50 AM
 */

abstract class Aligent_Emarsys_Abstract_Shell extends Mage_Shell_Abstract {
    protected $_resource = null;
    protected $_writer = null;
    protected $_reader = null;
    protected $_helper = null;

    public function __construct(){
        parent::__construct();
    }

    /**
     * Get the Aligent/Emarsys generalised helper object
     *
     * @return Aligent_Emarsys_Helper_Data
     */
    protected function getHelper(){
        if($this->_helper === null){
            $this->_helper = Mage::helper('aligent_emarsys');
        }
        return $this->_helper;
    }


    protected function getResource(){
        if($this->_resource === null){
            $this->_resource = Mage::getSingleton('core/resource');
        }
        return $this->_resource;
    }

    /**
     * Get the database writer interface
     * @return Varien_Db_Adapter_Interface
     */
    protected function getWriter(){
        if($this->_writer === null){
            $this->_writer = $this->getResource()->getConnection('core_write');
        }
        return $this->_writer;
    }

    /**
     * Get the database reader interface
     * @return Varien_Db_Adapter_Interface
     */
    protected function getReader(){
        if($this->_reader===null){
            $this->_reader = $this->getResource()->getConnection('core_read');
        }
        return $this->_reader;
    }

    protected function getTableName($fromClass, $isEntity = false){
        $class = Mage::getModel($fromClass);
        return $isEntity ? $class->getResource()->getEntityTable() : $class->getResource()->getMainTable();
    }
}
