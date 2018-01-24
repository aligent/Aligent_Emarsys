<?php

class Aligent_Emarsys_Model_Observer extends Varien_Event_Observer
{
    /** @var Aligent_Emarsys_Helper_Data */
    protected $_helper = null;

    public function __construct()
    {
    }

    /**
     * @return Aligent_Emarsys_Helper_Data|null
     */
    protected function getHelper()
    {
        if ($this->_helper === null) {
            $this->_helper = Mage::helper('aligent_emarsys');
        }
        return $this->_helper;
    }

    /**
     * Clear out the cart cookie when the order is made, try add in the user from the order.
     *
     * @param Varien_Event $observer
     */
    public function afterCheckoutSuccess($observer)
    {
        if ($this->getHelper()->isEnabled()) {
            $order = $observer->getOrder();
            $email = $order->getCustomerEmail();
            $this->getHelper()->emptyCartCookieAddEmail($email);

            // Also, ensure that the ERP (Harmony) knows about the customer
            $syncRecord = $this->getHelper()->ensureOrderSyncRecord($order);

        }
    }

    /**
     * Remove the user from the cart after a deliberate logout.
     * This always clears the cart.
     *
     * @param $observer
     */
    public function removeUserFromCookie($observer)
    {
        if ($this->getHelper()->isEnabled()) {
            $this->getHelper()->emptyCartCookie();
        }
    }

    /**
     * Add the user to cookie. Login would also cause cart to be updated so update the cart.
     * We cannot get the current cart as the carts will be merged or transfered (does not trigger event)
     *
     * Logic taken from login process for merging quotes
     *
     * @param $observer
     */
    public function addUserToCookie($observer)
    {
        if ($this->getHelper()->isEnabled()) {
            // Current quote.
            $quote = Mage::getModel('checkout/cart')->getQuote();

            // Customer quote
            $customerQuote = Mage::getModel('sales/quote')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->loadByCustomer(Mage::getSingleton('customer/session')->getCustomerId());

            if ($customerQuote->getId() && $quote->getId() != $customerQuote->getId()) {
                // Customer had a quote and it does not match the current quote.
                if ($quote->getId()) {
                    // This step will trigger a dispatched event and be updated after the carts have been merged.
                } else {
                    // The customer quote is transfered without dispatching an event so use that now.
                    $this->getHelper()->updateCookieFromQuote($customerQuote, true);
                }
                return;
            } else {
                // No logged in customer quote, no changes required.
            }

            $this->getHelper()->addUserToCookie();
        }
    }

    /**
     * The customer cart and guest cart are being merged.
     *
     * @param $observer
     */
    public function checkoutCartMergeUpdateCookie($observer)
    {
        if ($this->getHelper()->isEnabled()) {
            $quote = $observer->getQuote();

            $this->getHelper()->updateCookieFromQuote($quote, true);
        }
    }

    /**
     * Update the cart cookie when the cart is saved. Originally targetting add and remove dispatch events worked,
     * however no event is dispatched for update qty therefore using this event for everything.
     *
     * The cart is saved when the cart page is loaded, but
     * the function is a fairly light so should not cause too much overhead running twice on cart page qty update.
     *
     * @param $observer
     */
    public function checkoutCartSaveUpdateCookie($observer)
    {
        if ($this->getHelper()->isEnabled()) {
            $event = $observer->getEvent();
            $cart = $event->getCart();
            $quote = $cart->getQuote();

            $this->getHelper()->updateCookieFromQuote($quote);
        }
    }

    /**
     * Sweep the feeds directory and consolidate all
     * emarsys_consolidated-<store_code>.csv files into a
     * single emarsys_consolidated.csv file, then clean up
     * the individual store files.
     */
    public function consolidateEmarsysData(){
        Mage::log("Consolidate feed data");
        $vFeedDir = Mage::getBaseDir().Aligent_Feeds_Model_Writer_Abstract::FEED_PATH;
        $files = scandir($vFeedDir);

        $outputFile = fopen($vFeedDir . "/emarsys_consolidated.csv", "w");
        $hasHeaders = false;

        foreach($files as $file){
            preg_match("/emarsys-([^\.]+)\.csv/", $file, $match);
            if(sizeof($match) > 0){
                $inputFile = fopen($vFeedDir . "/$file", "r");
                $header = fgets($inputFile);
                if(!$hasHeaders){
                    fwrite($outputFile, $header);
                    $hasHeaders = true;
                }
                while( $line = fgets($inputFile) ) {
                    fwrite($outputFile, $line);
                }
                fclose($inputFile);
                unlink($vFeedDir . "/$file");
            }

        }
        fclose($outputFile);
    }

    /**
     * Check if the subscription record being saved is new.  If it is, ensure that there isn't an existing
     * subscription record for this email address and store id.  If there is, prevent the insertion of a
     * new one and update the existing one instead.
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function preventSubscriberInserts(Varien_Event_Observer $observer){
        /** @var $subscriber Mage_Newsletter_Model_Subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();
        if($subscriber->isObjectNew()){
            // Can we find an existing subscription record?  If so, update that record, not this one.
            $oldSubscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($subscriber->getEmail(), $subscriber->getStoreId());
            if($oldSubscriber->isObjectNew()) return false;

            if(!$oldSubscriber->getCustomerId()) $oldSubscriber->setCustomerId($subscriber->getCustomerId());
            $oldSubscriber->setStatus($subscriber->getStatus());
            $oldSubscriber->setCode($subscriber->getCode());
            $oldSubscriber->save();

            if($observer->getControllerAction()) {
                $observer->getControllerAction()->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * Handle Subscriber object saving process
     *
     * @param Varien_Event_Observer $observer
     * @return void|Varien_Event_Observer
     */
    public function handleSubscriber(Varien_Event_Observer $observer)
    {
        if($this->preventSubscriberInserts($observer)) return;
        if(Mage::registry('emarsys_newsletter_ignore')){
            return $this;
        }

        /** @var $subscriber Mage_Newsletter_Model_Subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();
        if ($this->getHelper()->isSubscriptionEnabled($subscriber->getStoreId())) {
            if($subscriber->getCustomerId()){
                // get customer for name details.
                $customer = Mage::getModel('customer/customer')->load($subscriber->getCustomerId());
            }else{
                $this->getHelper()->log("Find customer by email " . $subscriber->getSubscriberEmail());
                $customer = Mage::getModel('customer/customer')->loadByEmail($subscriber->getSubscriberEmail(), Mage::app()->getStore());
                if($customer->getId()){
                    $subscriber->setCustomerId($customer->getId());
                }
            }

            /** @var Aligent_Emarsys_Helper_Emarsys $emarsysHelper */
            $emarsysHelper = Mage::helper('aligent_emarsys/emarsys');
            $client = $emarsysHelper->getClient();

            $firstname = '';
            $lastname = '';
            $dob = '';
            $gender = '';
            $country = '';

            if ($customer->getId()) {
                $firstname = $customer->getFirstname();
                $lastname = $customer->getLastname();
                $dob = $customer->getDob();
                $gender = $customer->getGender();
                if($customer->getDefaultShippingAddress()){
                    $country = $customer->getDefaultShippingAddress()->getCountryModel()->getName();
                }
            } else {
                // check for subscriber data.
                if ($subscriber->getSubscriberFirstname() && $subscriber->getSubscriberLastname()) {
                    $firstname = $subscriber->getSubscriberFirstname();
                    $lastname = $subscriber->getSubscriberLastname();
                }
            }

            /** @var Aligent_Emarsys_Helper_Emarsys $helper */
            $helper = Mage::helper('aligent_emarsys/emarsys');
            $remoteSync = Mage::helper('aligent_emarsys')->ensureNewsletterSyncRecord(
                $subscriber->getId(),
                false,
                true,
                $firstname,
                $lastname,
                $gender,
                $dob,
                $country
            );

            if($remoteSync) {
                if ($subscriber->isSubscribed()) {
                    $result = $helper->addSubscriber($remoteSync->getId(), $firstname, $lastname, $subscriber->getSubscriberEmail(), $dob, $gender. $country);
                } else {
                    $result = $helper->removeSubscriber($remoteSync->getId(), $subscriber->getSubscriberEmail());
                }

                if($result && $result->getData()){
                    $remoteSync->setEmarsysId($result->getData()['id']);
                    $remoteSync->save();
                }
            }

        }
    }

    public function customerModelChanged(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if(Mage::registry('emarsys_customer_save_observer_executed')){
            return $this; //this method has already been executed once in this request (see comment below)
        }

        $harmonyIgnore = $customer->getHarmonyIgnoreFlag();
        $emarsysIgnore = $customer->getEmarsysIgnoreFlag();
        if($harmonyIgnore && $emarsysIgnore) return $this;

        $record = Mage::helper('aligent_emarsys')->ensureCustomerSyncRecord($customer->getId(), !$emarsysIgnore, !$harmonyIgnore);
        $record->setFirstName($customer->getFirstName());
        $record->setLastName($customer->getLastName());
        $record->setEmail($customer->getEmail());
        $record->save();
        Mage::register('emarsys_customer_save_observer_executed', true);
    }
}

