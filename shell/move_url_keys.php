<?php
$pathInfo =getcwd() . DIRECTORY_SEPARATOR . dirname($_SERVER['SCRIPT_FILENAME']);
require_once $pathInfo . '/abstract.php';
require_once $pathInfo . '/abstract_shell.php';

class Aligent_Emarsys_Shell_Move_Url_Keys extends Aligent_Emarsys_Abstract_Shell{

    public function run(){
        $writer = $this->getWriter();
        $reader = $this->getReader();
        $products = $this->getTableName('catalog/product', true);

        $productType = Mage::getModel('catalog/product')->getResource()->getType();

        $attribute = Mage::getModel('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'url_key');
        $attrId = $attribute->getId();

        $varchar = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_varchar');
        $urlKeys = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_url_key');

        $query = $reader->select();
        $query->from(array('e' => $products))->joinLeft(array('vuk'=>$varchar), 'e.entity_id=vuk.entity_id and vuk.attribute_id=' . $attrId)
            ->joinLeft(array('uk'=>$urlKeys), 'e.entity_id=uk.entity_id and vuk.store_id=uk.store_id and uk.attribute_id=' . $attrId);
        $query->where('uk.entity_id is null and vuk.entity_id is not null');

        $query->reset('columns');
        $query->columns(array('e.sku','e.entity_id', 'vuk.value_id as vuk_value_id', 'vuk.store_id as vuk_store_id','vuk.value as vuk_value','uk.store_id', 'uk.value'));

        $results = $reader->query($query);
        $iTotal = $results->rowCount();
        $this->console("Total " . $iTotal . "\n");

        $iCount = 0;
        $this->console("          ");
        foreach($results as $result){
            $data =                 array(
                'entity_type_id' => $productType,
                'attribute_id' => $attrId,
                'store_id' => $result['vuk_store_id'],
                'entity_id' => $result['entity_id'],
                'value' => $result['value']
            );

            $writer->insert(
                $urlKeys,
                $data
            );

            $iCount++;
            $this->console("\033[30D");
            $message = floor( ($iCount/ $iTotal * 10000) ) / 100;
            $message .= "% ($iCount of $iTotal)";
            $message = str_pad($message, 30, ' ');
            $this->console("$message");
            $writer->delete($varchar, 'value_id=' . $result['vuk_value_id'] );
        }
        $this->console("\nDone\n");
    }
}

$shell = new Aligent_Emarsys_Shell_Move_Url_Keys();
$shell->run();


