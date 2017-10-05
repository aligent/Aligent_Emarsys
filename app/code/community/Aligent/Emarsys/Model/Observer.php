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
     * Handle Subscriber object saving process
     *
     * @param Varien_Event_Observer $observer
     * @return void|Varien_Event_Observer
     */
    public function handleSubscriber(Varien_Event_Observer $observer)
    {
        if(Mage::registry('emarsys_newsletter_ignore')){
            return $this;
        }

        if ($this->getHelper()->isSubscriptionEnabled()) {
            /** @var $subscriber Mage_Newsletter_Model_Subscriber */
            $subscriber = $observer->getEvent()->getSubscriber();

            // get customer for name details.
            $customer = Mage::getModel('customer/customer')->load($subscriber->getCustomerId());

            $firstname = '';
            $lastname = '';
            $dob = '';
            $gender = '';

            if ($customer->getId()) {
                $firstname = $customer->getFirstname();
                $lastname = $customer->getLastname();
                $dob = $customer->getDob();
                $gender = $customer->getGender();
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
                $dob
            );

            if ($subscriber->isSubscribed()) {
                $result = $helper->addSubscriber($remoteSync->getId(), $firstname, $lastname, $subscriber->getSubscriberEmail(), $dob, $gender);
            }else{
                $result = $helper->removeSubscriber($remoteSync->getId(), $subscriber->getSubscriberEmail());
            }

            if($result && $result->getData()){
                $remoteSync->setEmarsysId($result->getData()['id']);
                $remoteSync->save();
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

        Mage::helper('aligent_emarsys')->ensureCustomerSyncRecord($customer->getId(), !$emarsysIgnore, !$harmonyIgnore);

        Mage::register('emarsys_customer_save_observer_executed', true);
    }
}

