<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 11/29/17
 * Time: 12:58 PM

 * IMPORTANT NOTE: This was refactored to use the core/resource connections core_read and core_write for
 * speed reasons (it's significantly faster).  While this does bypass model getters and setters, the fields
 * in question do not have getters and setters that we are concerned about for this exercise.  We're also
 * fixing data duplication and don't want event observers firing while we clean it up.
 */

require_once 'abstract.php';

class Aligent_Emarsys_Shell_Sync_Newsletter_To_Customer_Email extends Mage_Shell_Abstract {

    public function run(){
        $this->rehookDuplicateCustomerIds();
        $this->removeSubscriberDuplicates();
    }

    protected function removeSubscriberDuplicates(){
        /** @var Aligent_Emarsys_Helper_LightweightDataHelper $helper */
        $helper = Mage::helper('aligent_emarsys/lightweightDataHelper');

        /**
         * Fetch all newsletter subscriptions where there are multiple subscriptions for the same customer ID
         *
         * Then remove all but the latest subscription record.
         */
        $customertable = Mage::getModel('customer/customer')->getResource()->getEntityTable();
        $newsletterTable = Mage::getModel('newsletter/subscriber')->getResource()->getMainTable();

        $subQuery = $helper->getReader()->select()->from($newsletterTable)
            ->reset(Varien_Db_Select::COLUMNS)
            ->columns('customer_id')->where('customer_id > 0')->group('customer_id')->having('count(*) > 1 ');

        $sql = $helper->getReader()->select()->from($newsletterTable)
            ->joinLeft($customertable, "$newsletterTable.subscriber_email=$customertable.email AND $newsletterTable.store_id = $customertable.store_id")
            ->reset(Varien_Db_Select::COLUMNS)
            ->columns(["$newsletterTable.subscriber_id", "$newsletterTable.customer_id", "$customertable.entity_id", "$customertable.email"])
            ->where(" customer_id in ($subQuery)")->order('customer_id')->order('subscriber_id');

        $rows = $sql->query()->fetchAll();

        $subData = array();
        foreach($rows as $row){
            $customerId = $row['customer_id'];
            if(!isset($subData[$customerId])) $subData[$customerId] = array();
            $subData[$customerId][] = $row['subscriber_id'];
        }

        foreach($subData as $key => $item){
            // Only go up to the second to last item, because we want to keep the most recent one
            for($i=0; $i < sizeof($item) - 1; $i++){
                $helper->getWriter()->delete($newsletterTable, 'subscriber_id='.$item[$i]);
            }
        }
    }

    protected function rehookDuplicateCustomerIds(){
        /** @var Aligent_Emarsys_Helper_LightweightDataHelper $helper */
        $helper = Mage::helper('aligent_emarsys/lightweightDataHelper');

        /**
         * Fetch all newsletter subscriptions where there are multiple subscriptions for the same customer ID
         * AND there is a mismatch between customer email and subscriber email AND a different customer entity
         * exists with the newsletter subscription email address.
         *
         * Then re-hook those subscribers to their customers.
         */
        $customertable = Mage::getModel('customer/customer')->getResource()->getEntityTable();
        $newsletterTable = Mage::getModel('newsletter/subscriber')->getResource()->getMainTable();

        $subQuery = $helper->getReader()->select()->from($newsletterTable)
            ->reset(Varien_Db_Select::COLUMNS)
            ->columns('customer_id')->where('customer_id > 0')->group('customer_id')->having('count(*) > 1 ');

        $sql = $helper->getReader()->select()->from($newsletterTable )
            ->join($customertable, "$newsletterTable.subscriber_email=$customertable.email AND $newsletterTable.store_id = $customertable.store_id")
            ->reset(Varien_Db_Select::COLUMNS)
            ->columns(["$newsletterTable.subscriber_id", "$newsletterTable.customer_id", "$customertable.entity_id", "$customertable.email"])
            ->where(" customer_id in ($subQuery) and $customertable.entity_id != $newsletterTable.customer_id ");

        $rows = $sql->query()->fetchAll();
        foreach($rows as $row){
            $data = array( 'customer_id' => $row['entity_id'] );
            $helper->save($newsletterTable, $data, 'subscriber_id', $row['subscriber_id']);
        }
    }
}

$shell = new Aligent_Emarsys_Shell_Sync_Newsletter_To_Customer_Email();
$shell->run();