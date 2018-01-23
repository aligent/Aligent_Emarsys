<?php

class Aligent_Emarsys_Block_Emarsys extends Mage_Core_Block_Template {
    protected $_helper = null;

    public function _construct() {}

    protected function getEmarsysHelper() {
        if ($this->_helper === null) {
            $this->_helper = Mage::helper('aligent_emarsys');
        }
        return $this->_helper;
    }

    public function isEnabled() {
        return $this->getEmarsysHelper()->isEnabled();
    }

    public function getCookieName() {
        return $this->getEmarsysHelper()->getCookieName();
    }

    public function getMerchantId() {
        return $this->getEmarsysHelper()->getMerchantId();
    }

    public function getScarabJsUrl() {
        return $this->getEmarsysHelper()->getScarabJsUrl();
    }

    public function getSendEmail() {
        return ($this->getEmarsysHelper()->getSendEmail()) ? 1 : 0;
    }

    public function isTestMode() {
        return ($this->getEmarsysHelper()->isTestMode()) ? 1 : 0;
    }

    public function shouldSendParentSku() {
        return ($this->getEmarsysHelper()->shouldSendParentSku()) ? 1 : 0;
    }
    public function getSendWebsiteCode() {
        return ($this->getEmarsysHelper()->getSendWebsiteCode()) ? 1 : 0;
    }

    public function shouldUseStoreSku()
    {
        return $this->getEmarsysHelper()->shouldUseStoreSku() ? 1 : 0;
    }

    public function getCategoryId() {
        return Mage::registry('current_category')->getId();
    }

    public function getCategoryPath() {
        return Mage::helper('aligent_feeds')->getCategoryPath($this->getCategoryId());
    }

    /**
     * Get the current products SKU
     *
     * Includes translation for when the 'use store sku' config value is set which converts a
     * sku from 'abc123def' to 'mystore_abc123def'
     *
     * @return string
     */
    public function getProductSku() {
        $sku = Mage::registry('current_product')->getSku();

        if ($this->shouldUseStoreSku()) {
            $translator = new Aligent_Emarsys_Model_Translator_Item();
            $sku = $translator->translate(['sku' => $sku], null, Mage::app()->getStore());
        }

        return $sku;
    }

    public function getSearchTerm() {
        return Mage::helper('catalogsearch')->getQueryText();
    }

    /**
     * Get the order id and the cart contents for the success page.
     *
     * @return array
     */
    public function getSuccessInfo() {
        $lastorderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $lastOrder = Mage::getSingleton('sales/order')->loadByIncrementId($lastorderId);
        $items = $lastOrder->getAllItems();

        $cartArray = Mage::helper('aligent_emarsys')->getFormattedItemArray($items);

        return [$lastorderId, Mage::helper('core')->jsonEncode($cartArray)];
    }

    /**
     * For some reason, getUrl doesn't follow current secure/insecure protocol by default.
     */
    public function getSubscriptionUrl(){
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = Mage::app()->getFrontController()->getRequest();
        $isSecure = $request->isSecure();
        $params = array('_secure' => $isSecure);
        $url = Mage::getUrl('aligent_emarsys/index/newslettersubscribe', $params);
        return $url;

    }

}