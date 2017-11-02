<?php
class Aligent_Emarsys_Helper_Data extends Mage_Core_Helper_Abstract {
    protected $_feedStockFromSimple = null;
    protected $_includeSimpleParents = null;
    protected $_includeDisabled = null;
    protected $_harmonyDumpFile = null;

    protected $_cookieName = null;
    protected $_enabled = null;
    protected $_merchantId = null;
    protected $_scarabJsUrl = null;
    protected $_sendEmail = null;
    protected $_isTestMode = null;
    protected $_sendParentSku = null;
    protected $_subscriptionSignupUrl = null;
    protected $_subscriptionSignupTimeout = null;

    protected $_harmonyFTPServer = null;
    protected $_harmonyFTPPort = null;
    protected $_harmonyFTPUser = null;
    protected $_harmonyFTPPass = null;
    protected $_harmonyFTPImport = null;
    protected $_harmonyFTPExport = null;
    protected $_harmonyDebtor = null;
    protected $_harmonyTerminalId = null;
    protected $_harmonyUserId = null;
    protected $_harmonyNamekeyPrefix = null;
    protected $_harmonyIdField = null;
    protected $_harmonyWebAgent = null;

    protected $_emarsysDebug = null;
    protected $_emarsysAPIUser = null;
    protected $_emarsysAPISecret = null;
    protected $_emarsysSubscriptionField = null;
    protected $_emarsysVoucherField = null;
    protected $_emarsysDobField = null;

    const XML_FEED_STOCK_FROM_SIMPLE = 'aligent_emarsys/feed/stock_from_simple';
    const XML_FEED_INCLUDE_SIMPLE_PARENTS = 'aligent_emarsys/feed/include_simple_parents';
    const XML_FEED_INCLUDE_DISABLED = 'aligent_emarsys/feed/include_disabled';

    const XML_EMARSYS_ENABLED_PATH = 'aligent_emarsys/settings/enabled';
    const XML_EMARSYS_COOKIE_NAME_PATH = 'aligent_emarsys/settings/cookie_key';
    const XML_EMARSYS_MERCHANT_ID_PATH = 'aligent_emarsys/settings/merchant_id';
    const XML_EMARSYS_SCARAB_JS_URL_PATH = 'aligent_emarsys/settings/scarab_url';
    const XML_EMARSYS_SEND_EMAIL_PATH = 'aligent_emarsys/settings/send_email';
    const XML_EMARSYS_TEST_MODE_PATH = 'aligent_emarsys/settings/test_mode';
    const XML_EMARSYS_SEND_PARENT_SKU_PATH = 'aligent_emarsys/settings/send_parent_sku';

    const XML_EMARSYS_SUBSCRIPTION_ENABLED_PATH = 'aligent_emarsys/subscription/enabled';
    const XML_EMARSYS_SUBSCRIPTION_BASE_URL_PATH = 'aligent_emarsys/subscription/base_url';
    const XML_EMARSYS_SUBSCRIPTION_CURL_TIMEOUT_PATH = 'aligent_emarsys/subscription/curl_timeout';

    const KEY_COOKIE_USER = 'user';
    const KEY_COOKIE_USER_ID = 'id';
    const KEY_COOKIE_USER_EMAIL = 'email';
    const KEY_COOKIE_CART = 'cart';

    const XML_EMARSYS_HARMONY_FTP_SERVER = 'aligent_emarsys/harmony_settings/harmony_ftp_server';
    const XML_EMARSYS_HARMONY_FTP_PORT = 'aligent_emarsys/harmony_settings/harmony_ftp_port';
    const XML_EMARSYS_HARMONY_FTP_USER = 'aligent_emarsys/harmony_settings/harmony_ftp_user';
    const XML_EMARSYS_HARMONY_FTP_PASS = 'aligent_emarsys/harmony_settings/harmony_ftp_password';
    const XML_EMARSYS_HARMONY_FTP_IMPORT = 'aligent_emarsys/harmony_settings/harmony_ftp_import_path';
    const XML_EMARSYS_HARMONY_FTP_EXPORT = 'aligent_emarsys/harmony_settings/harmony_ftp_export_path';
    const XML_EMARSYS_HARMONY_WEB_AGENT = 'aligent_emarsys/harmony_settings/web_agent';
    const XML_EMARSYS_HARMONY_DEBTOR = 'aligent_emarsys/harmony_settings/web_debtor';
    const XML_EMARSYS_HARMONY_TERMINAL = 'aligent_emarsys/harmony_settings/web_terminal';
    const XML_EMARSYS_HARMONY_USER = 'aligent_emarsys/harmony_settings/web_user';
    const XML_EMARSYS_HARMONY_PREFIX = 'aligent_emarsys/harmony_settings/namekey_prefix';
    const XML_HARMONY_DUMP_FILE = 'aligent_emarsys/harmony_settings/harmony_dump_file';

    const XML_EMARSYS_API_DEBUG_MODE = 'aligent_emarsys/emarsys_api_settings/emarsys_debug';
    const XML_EMARSYS_API_USER = 'aligent_emarsys/emarsys_api_settings/emarsys_username';
    const XML_EMARSYS_API_SECRET = 'aligent_emarsys/emarsys_api_settings/emarsys_secret';
    const XML_EMARSYS_API_SUBSCRIPTION_FIELD = 'aligent_emarsys/emarsys_api_settings/emarsys_subscription_field_id';
    const XML_EMARSYS_API_VOUCHER_FIELD = 'aligent_emarsys/emarsys_api_settings/emarsys_voucher_field_id';
    const XML_EMARSYS_API_DOB_FIELD = 'aligent_emarsys/emarsys_api_settings/emarsys_dob_field_id';
    const XML_EMARSYS_API_HARMONY_ID_FIELD = 'aligent_emarsys/emarsys_api_settings/harmony_id_field';

    public function getHarmonyWebAgent(){
        if($this->_harmonyWebAgent === null){
            $this->_harmonyWebAgent = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_WEB_AGENT);
        }
        return $this->_harmonyWebAgent;
    }

    /**
     * Retrieve the debug mode flag for the Emarsys API.
     *
     * @return bool|null
     */
    public function getEmarsysDebug(){
        if($this->_emarsysDebug === null){
            $this->_emarsysDebug = Mage::getStoreConfigFlag(self::XML_EMARSYS_API_DEBUG_MODE);
        }
        return $this->_emarsysDebug;
    }

    public function getHarmonyFileDump(){
        if($this->_harmonyDumpFile === null) {
            $this->_harmonyDumpFile = Mage::getStoreConfigFlag(self::XML_HARMONY_DUMP_FILE);
        }
        return $this->_harmonyDumpFile;
    }

    public function getGetStockFromSimpleProduct(){
        if($this->_feedStockFromSimple === null ){
            $this->_feedStockFromSimple = Mage::getStoreConfig(self::XML_FEED_STOCK_FROM_SIMPLE) == 1;
        }
        return $this->_feedStockFromSimple;
    }

    public function getIncludeSimpleParents(){
        if($this->_includeSimpleParents === null ){
            $this->_includeSimpleParents = Mage::getStoreConfig(self::XML_FEED_INCLUDE_SIMPLE_PARENTS) == 1;
        }
        return $this->_includeSimpleParents;

    }

    public function getIncludeDisabled(){
        if($this->_includeDisabled === null ){
            $this->_includeDisabled = Mage::getStoreConfig(self::XML_FEED_INCLUDE_DISABLED) == 1;
        }
        return $this->_includeDisabled;
    }

    /**
     * Get the Harmony ID field to populate in Emarsys, if specified.
     */
    public function getHarmonyIdField(){
        if($this->_harmonyIdField === null ){
            $this->_harmonyIdField = Mage::getStoreConfig(self::XML_EMARSYS_API_HARMONY_ID_FIELD);
        }
        return $this->_harmonyIdField;
    }

    /**
     * Get the DOB field to use with Emarsys.  Blank if not collecting DOB.
     * @return string
     */
    public function getEmarsysDobField(){
        if($this->_emarsysDobField === null){
            $this->_emarsysDobField = Mage::getStoreConfig(self::XML_EMARSYS_API_DOB_FIELD);
            if($this->_emarsysDobField == '-1') $this->_emarsysDobField='';
        }
        return $this->_emarsysDobField;
    }

    /**
     * Get the Emarsys API Username
     * @return string
     */
    public function getEmarsysAPIUser($storeId = null){
        if($this->_emarsysAPIUser === null){
            $this->_emarsysAPIUser = Mage::getStoreConfig(self::XML_EMARSYS_API_USER, $storeId);
        }
        return $this->_emarsysAPIUser;
    }

    /**
     * Get the Emarsys API Secret
     * THIS IS RETURNED AS DECRYPTED PLAIN TEXT.
     * @return string
     */
    public function getEmarsysAPISecret($storeId = null){
        if($this->_emarsysAPISecret === null){
            $this->_emarsysAPISecret = $this->decrypt( Mage::getStoreConfig(self::XML_EMARSYS_API_SECRET, $storeId ) );

        }
        return $this->_emarsysAPISecret;
    }

    /**
     * Get the Emarsys API Subscription field
     * @return string
     */
    public function getEmarsysAPISubscriptionField(){
        if($this->_emarsysSubscriptionField === null){
            $this->_emarsysSubscriptionField = Mage::getStoreConfig(self::XML_EMARSYS_API_SUBSCRIPTION_FIELD);
            if($this->_emarsysSubscriptionField=='-1') $this->_emarsysSubscriptionField = '';
        }
        return $this->_emarsysSubscriptionField;
    }

    /**
     * Get the Emarsys API voucher field
     * @return string
     */
    public function getEmarsysAPIVoucherField(){
        if($this->_emarsysVoucherField === null){
            $this->_emarsysVoucherField = Mage::getStoreConfig(self::XML_EMARSYS_API_VOUCHER_FIELD);
            if($this->_emarsysVoucherField =='-1') $this->_emarsysVoucherField = '';
        }
        return $this->_emarsysVoucherField;
    }

    /**
     * Get the Harmony FTP server
     *
     * @return string
     */
    public function getHarmonyFTPServer(){
        if($this->_harmonyFTPServer === null){
            $this->_harmonyFTPServer = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_FTP_SERVER);
        }
        return $this->_harmonyFTPServer;
    }

    /**
     * Get the Harmony FTP server's port.  If unspecified, default to 21
     *
     * @return string
     */
    public function getHarmonyFTPPort(){
        if($this->_harmonyFTPPort === null){
            $this->_harmonyFTPPort = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_FTP_PORT);
            if(!is_numeric($this->_harmonyFTPPort)) $this->_harmonyFTPPort = 21;
        }
        return $this->_harmonyFTPPort;
    }

    /**
     * Get the Harmony FTP username
     *
     * @return string
     */
    public function getHarmonyFTPUsername(){
        if($this->_harmonyFTPUser === null){
            $this->_harmonyFTPUser = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_FTP_USER);
        }
        return $this->_harmonyFTPUser;
    }

    /**
     * Get the Harmony FTP password.
     * THIS IS RETURNED AS DECRYPTED PLAIN TEXT.
     *
     * @return string
     */
    public function getHarmonyFTPPassword(){
        if($this->_harmonyFTPPass === null){
            $this->_harmonyFTPPass = $this->decrypt( Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_FTP_PASS) );
        }
        return $this->_harmonyFTPPass;
    }

    /**
     * Get directory on the Harmony FTP server to place files for export to Harmony.
     *
     * @return string
     */
    public function getHarmonyFTPExportDir(){
        if($this->_harmonyFTPExport === null){
            $this->_harmonyFTPExport = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_FTP_EXPORT);
        }
        return $this->_harmonyFTPExport;
    }

    /**
     * Get directory on the Harmony FTP server to monitor for import files from Harmony.
     *
     * @return string
     */
    public function getHarmonyFTPImportDir(){
        if($this->_harmonyFTPImport === null){
            $this->_harmonyFTPImport = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_FTP_IMPORT);
        }
        return $this->_harmonyFTPImport;
    }

    /**
     * Get the Harmony debtor namekey
     *
     * @return string
     */
    public function getHarmonyDebtorNamekey(){
        if($this->_harmonyDebtor === null){
            $this->_harmonyDebtor = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_DEBTOR);
        }
        return $this->_harmonyDebtor;
    }

    /**
     * Get the Harmony terminal ID
     *
     * @return string
     */
    public function getHarmonyTerminalId(){
        if($this->_harmonyTerminalId === null){
            $this->_harmonyTerminalId = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_TERMINAL);
        }
        return $this->_harmonyTerminalId;

    }

    /**
     * Get the Harmony user ID
     *
     * @return string
     */
    public function getHarmonyUserId(){
        if($this->_harmonyUserId === null){
            $this->_harmonyUserId = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_USER);
        }
        return $this->_harmonyUserId;

    }

    /**
     * Get the Harmony customer namekey prefix
     *
     * @return string
     */
    public function getHarmonyNamekeyPrefix(){
        if($this->_harmonyNamekeyPrefix === null){
            $this->_harmonyNamekeyPrefix = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_PREFIX);
        }
        return $this->_harmonyNamekeyPrefix;

    }


    /**
     * Get the cookie name.
     *
     * @return string
     */
    public function getCookieName() {
        if ($this->_cookieName === null) {
            $this->_cookieName = Mage::getStoreConfig(self::XML_EMARSYS_COOKIE_NAME_PATH);
        }
        return $this->_cookieName;
    }

    /**
     * Is the Emarsys module enabled.
     *
     * @return bool
     */
    public function isEnabled() {
        if ($this->_enabled === null) {
            $this->_enabled = Mage::getStoreConfigFlag(self::XML_EMARSYS_ENABLED_PATH);
        }
        return $this->_enabled;
    }

    /**
     * Get the Emarsys Merchant Id.
     *
     * @return string
     */
    public function getMerchantId() {
        if ($this->_merchantId === null) {
            $this->_merchantId = Mage::getStoreConfig(self::XML_EMARSYS_MERCHANT_ID_PATH);
        }
        return $this->_merchantId;
    }

    /**
     * Get the scarab URL
     *
     * @return string
     */
    public function getScarabJsUrl() {
        if ($this->_scarabJsUrl === null) {
            $this->_scarabJsUrl = Mage::getStoreConfig(self::XML_EMARSYS_SCARAB_JS_URL_PATH);
        }
        return $this->_scarabJsUrl;
    }

    /**
     * Should we send the users email or ID
     *
     * @return string
     */
    public function getSendEmail() {
        if ($this->_sendEmail === null) {
            $this->_sendEmail = Mage::getStoreConfigFlag(self::XML_EMARSYS_SEND_EMAIL_PATH);
        }
        return $this->_sendEmail;
    }

    /**
     * Are we in test mode.
     *
     * @return string
     */
    public function isTestMode() {
        if ($this->_isTestMode === null) {
            $this->_isTestMode = Mage::getStoreConfigFlag(self::XML_EMARSYS_TEST_MODE_PATH);
        }
        return $this->_isTestMode;
    }

    /**
     * Should the parent sku be returned instead of the child
     *
     * @return bool
     */
    public function shouldSendParentSku() {
        if ($this->_sendParentSku === null) {
            $this->_sendParentSku = Mage::getStoreConfigFlag(self::XML_EMARSYS_SEND_PARENT_SKU_PATH);
        }
        return $this->_sendParentSku;
    }

    /**
     * Should the parent sku be returned instead of the child
     *
     * @return bool
     */
    public function isSubscriptionEnabled( $storeId = null ) {
        return Mage::getStoreConfigFlag(self::XML_EMARSYS_SUBSCRIPTION_ENABLED_PATH, $storeId);
    }

    /**
     * Get the subscription base URL for emarsys
     *
     * @return string
     */
    public function getSubscriptionBaseUrl() {
        if ($this->_subscriptionSignupUrl === null) {
            $this->_subscriptionSignupUrl = Mage::getStoreConfig(self::XML_EMARSYS_SUBSCRIPTION_BASE_URL_PATH);
        }
        return $this->_subscriptionSignupUrl;
    }

    /**
     * Get the curl timeout
     *
     * @return string
     */
    public function getSubscriberCurlTimeout() {
        if ($this->_subscriptionSignupTimeout === null) {
            $this->_subscriptionSignupTimeout = Mage::getStoreConfig(self::XML_EMARSYS_SUBSCRIPTION_CURL_TIMEOUT_PATH);
        }
        return $this->_subscriptionSignupTimeout;
    }

    /**
     * Retrieve the contents from cookie.
     *
     * @return array
     */
    public function getCookie()
    {
        $cookieContents = Mage::helper('core')->jsonDecode(Mage::getModel('core/cookie')->get($this->getCookieName()));

        if ($cookieContents === null) {
            $cookieContents = array();
        }

        return (array)$cookieContents;
    }

    /**
     * Set the contents cookie.
     *
     * @param $cookie
     */
    protected function setCookie($cookie=null)
    {
        if ($cookie === null) {
            $cookie = array();
        }
        // Set HTTP to false to allow JS to access the cookie after logout
        Mage::getModel('core/cookie')->set($this->getCookieName(), Mage::helper('core')->jsonEncode($cookie), null, null, null, null, false);
    }

    protected function _updateCookie($cart=array(), $user=null) {
        if ($user === null) {
            $user = $this->getEmptyUser();
        }

        $cookie = array(
            self::KEY_COOKIE_USER => $user,
            self::KEY_COOKIE_CART => $cart
        );
        $this->setCookie($cookie);
    }


    /**
     * Standard transaction should get the user from cookie.
     *
     * @return array|Aligent_Emarsys_Block_Cartcookie|Mage_Sales_Model_Quote
     */
    protected function getUserFromCookie() {
        $cookie = $this->getCookie();
        if (isset($cookie[self::KEY_COOKIE_USER]) && ($cookie[self::KEY_COOKIE_USER][self::KEY_COOKIE_USER_ID] !== null || $cookie[self::KEY_COOKIE_USER][self::KEY_COOKIE_USER_EMAIL] !== null)) {
            return $cookie[self::KEY_COOKIE_USER];
        }
        return $this->getEmptyUser();
    }

    /*
     * After a login we want to get the user from session only once.
     */
    protected function getUserFromSession() {
        $sessionCustomer = Mage::getSingleton("customer/session");
        if($sessionCustomer->isLoggedIn()) {
            $customer = $sessionCustomer->getCustomer();
            return array(
                self::KEY_COOKIE_USER_ID => $customer->getEntityId(),
                self::KEY_COOKIE_USER_EMAIL => $customer->getEmail(),
            );
        }
        return $this->getEmptyUser();
    }

    /**
     * Return an empty user. Can specify the email if it is known.
     *
     * @param null $email
     * @return array
     */
    protected function getEmptyUser($email=null) {
        return array(
            self::KEY_COOKIE_USER_ID => null,
            self::KEY_COOKIE_USER_EMAIL => $email,
        );
    }

    /**
     * Add the user to the cookie and keep cart contents
     */
    protected function getUserFromQuote($quote) {
        return array(
            self::KEY_COOKIE_USER_ID => null,
            self::KEY_COOKIE_USER_EMAIL => null,
        );
    }

    /**
     * Empty the cart cookie contents and empty user
     */
    public function emptyCartCookie() {
        $this->_updateCookie();
    }

    /**
     * Empty the cart cookie contents but keep user
     */
    public function emptyCartCookieKeepUser() {
        $this->_updateCookie(array(), $this->getUserFromCookie());
    }

    /**
     * Empty the cart cookie contents and set users email from checkout
     */
    public function emptyCartCookieAddEmail($email=null) {
        $this->_updateCookie(array(), $this->getEmptyUser($email));
    }

    /**
     * Remove the user from the cart and keep contents of cart.
     */
    public function removeUserFromCookie() {
        $cookie = $this->getCookie();
        $this->_updateCookie($cookie[self::KEY_COOKIE_CART], $this->getEmptyUser());
    }

    /**
     * Add the user to the cookie and keep cart contents
     */
    public function addUserToCookie() {
        $cookie = $this->getCookie();
        $this->_updateCookie($cookie[self::KEY_COOKIE_CART], $this->getUserFromSession());
    }

    /**
     * Add the user to the cookie and keep cart contents
     */
    public function addEmailToCookie($email) {
        $cookie = $this->getCookie();
        $this->_updateCookie($cookie[self::KEY_COOKIE_CART], $this->getEmptyUser($email));
    }

    /**
     * Update the cart cookie with the given quote. If no quote provided then find the quote.
     * Check the user in the session or cookie is deteremined by the flag.
     * Should always use cookie unless login/logout was explicitly called.
     *
     * @param null $quote
     * @param false $userAfterLogin
     */
    public function updateCookieFromQuote($quote=null, $userAfterLogin=false) {
        if ($quote === null) {
            $quote = Mage::getModel('checkout/cart')->getQuote();
        }

        $cartArray = $this->getFormattedItemArray($quote->getAllItems());

        $setUser = null;
        if ($userAfterLogin) {
            $setUser = $this->getUserFromSession();
        } else {
            $setUser = $this->getUserFromCookie();
        }

        $this->_updateCookie($cartArray, $setUser);
    }

    /**
     * Get the emarsys formatted cart array of items.
     *
     * @param $items
     * @return Aligent_Emarsys_Block_Cartcookie|Mage_Sales_Model_Quote
     */
    public function getFormattedItemArray($items) {
        $cartArray = array();

        foreach($items as $item) {
            // Configurable products cannot be in the cart so skip it.
            if ($item->getProductType() == 'configurable') {
                continue;
            }

            $itemToUse = $item;
            // If has parent, use parent info.
            if ($item->getParentItem() !== null) {
                //information about the product is on the parent *(ie. price etc.)
                $itemToUse = $item->getParentItem();
            }

            $tempItem = array();

            $tempItem['id'] = $itemToUse->getSku();

            if ($itemToUse->getProductType() == 'configurable') {
                $product = $itemToUse->getProduct();
                // getSku() will get the child, must grab the data specifically.
                $tempItem['parentid'] = $product->getData('sku');
            }

            $qty = $itemToUse->getQty();

            if ($qty === null) {
                $qty = $itemToUse->getQtyOrdered();
            }

            $tempItem['qty'] = $qty;
            $tempItem['price'] = $itemToUse->getPriceInclTax();
            $tempItem['price_total'] = $itemToUse->getRowTotalInclTax();

            $cartArray[$tempItem['id']] = $tempItem;
        }

        return $cartArray;
    }

    /**
     * @param $customer Mage_Customer_Model_Customer
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function getCustomerSubscriber($customer){
        return Mage::getModel('newsletter/subscriber')
            ->getCollection()->addFieldToSelect('subscriber_status')->addFieldToFilter('customer_id',$customer->getId())->getFirstItem();
    }

    /**
     * @param $email string
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function getEmailSubscriber($email){
        return Mage::getModel('newsletter/subscriber')->loadByEmail($email);
    }

    /**
     * @param $customer
     * @return bool
     */
    public function isCustomerSubscribed($customer){
        $subscriber = $this->getCustomerSubscriber($customer);
        return $subscriber->getSubscriberStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
    }

    /**
     * @param string $string Value to decrypt
     * @return string The decrypted value
     */
    protected function decrypt($string){
        /** @var Mage_Core_Helper_Data $core */
        $core = Mage::helper('core');
        return $core->decrypt( $string );
    }

    /**
     * Locate an Aligent_Emarsys_Remote_System_Sync_flags record
     * with the given customer_entity_id, or return a new model
     * with the ID set if not found.
     *
     * @param $id The customer entity id to find
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function findCustomerSyncRecord($id){
        /** @var Aligent_Emarsys_Model_RemoteSystemSyncFlags $remoteSync */
        $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($id, 'customer_entity_id');

        // Is it a valid customer?
        $customer = Mage::getModel('customer/customer')->load($id);

        // Is there a record for this email address already but with no customer ID?
        if(!$remoteSync->getId()) {
            $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($customer->getEmail(), 'email');
            if($remoteSync->getId() && $remoteSync->getCustomerEntityId()) $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags');
        }
        // Set this again, just in case we're a new record.
        $remoteSync->setCustomerEntityId($id);
        return $remoteSync;
    }

    /**
     * @param $subscriber
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function findNewsletterSyncRecord($subscriber){
        $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($subscriber->getId(), 'newsletter_subscriber_id');
        // If we don't have a sync for this subscriber and the subscriber is a customer,
        // do we have a sync for that customer?
        if(!$remoteSync->getId() && $subscriber->getCustomerId()){
            $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($subscriber->getCustomerId(), 'customer_entity_id');
        }

        // We still don't have a sync, so let's see if we can match on email.
        if(!$remoteSync->getId()){
            $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($subscriber->getSubscriberEmail(), 'email');
            if($remoteSync->getId() ){
                if($remoteSync->getNewsletterSubscriberId()){
                    $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags');
                }else{
                    if(!$subscriber->getCustomerId()) $subscriber->setCustomerId($remoteSync->getCustomerEntityId());
                }
            }
        }
        $remoteSync->setNewsletterSubscriberId($subscriber->getId());
        return $remoteSync;
    }

    /**
     * Ensure that for a given newsletter subscriber, a remoteSystemSyncFlags record exists
     * and set the dirty flags as required
     *
     * @param int $id The newsletter_subscriber ID
     * @param bool $emarsysFlag Whether to mark as needing Emarsys sync (true) or not (false)
     * @param bool $harmonyFlag Whether to mark as needing Harmony sync (true) or not (false)
     * @param string $firstName First name to populate the record with
     * @param string $lastName Last name to populate the record with
     * @param string $gender Gender to populate the record with
     * @param string $dob Date of birth to populate the record with
     *
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function ensureNewsletterSyncRecord($id, $emarsysFlag = true, $harmonyFlag = true, $firstName = null, $lastName = null, $gender = null, $dob = null){
        $subscriber = Mage::getModel('newsletter/subscriber')->load($id);
        if(!$subscriber->getId()){
            return null;// If we weren't passed a valid newsletter subscriber ID, just bail
        }

        $remoteSync = $this->findNewsletterSyncRecord($subscriber);
        $remoteSync->setCustomerEntityId($subscriber->getCustomerId());
        $remoteSync->setHarmonySyncDirty($harmonyFlag);
        $remoteSync->setEmarsysSyncDirty($emarsysFlag);

        if($firstName) $remoteSync->setFirstName($firstName);
        if($lastName) $remoteSync->setLastName($lastName);
        if($dob) $remoteSync->setDob($dob);
        if($gender) $remoteSync->setGender($gender);

        $remoteSync->setEmail($subscriber->getSubscriberEmail());
        $remoteSync->save();

        return $remoteSync;
    }

    /**
     * @param int $id The customer_entity_id
     * @param bool $emarsysFlag Whether to mark the record dirty for Emarsys
     * @param bool $harmonyFlag Whether to mark the record dirty for Harmony
     *
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function ensureCustomerSyncRecord($id, $emarsysFlag = true, $harmonyFlag = true){
        $remoteSync = $this->findCustomerSyncRecord($id);
        $remoteSync->setHarmonySyncDirty($harmonyFlag);
        $remoteSync->setEmarsysSyncDirty($emarsysFlag);
        $remoteSync->save();
        return $remoteSync;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function ensureOrderSyncRecord($order){
        // Is there a customer ID?
        if($order->getCustomerIsGuest()){
            // Ok, let's try to find a subscriber
            /** @var Mage_Newsletter_Model_Subscriber  $subscriber */
            $subscriber = Mage::getModel('newsletter/subscriber');
            $subscriber->loadByEmail($order->getCustomerEmail());
            if(!$subscriber->getId()){
                // If they're not a subscriber, add a record just so
                // we have enough data for a namekey
                Mage::register('emarsys_newsletter_ignore', true);
                $subscriber->setSubscriberEmail($order->getCustomerEmail());
                $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                $subscriber->save();
            }
            $remoteSync = $this->ensureNewsletterSyncRecord($subscriber->getId(),true,true, $order->getCustomerFirstname(),
                $order->getCustomerLastname(), $order->getCustomerGender(), $order->getCustomerDob());
        }else{
            $remoteSync = $this->ensureCustomerSyncRecord($order->getCustomerId(), true, true);
        }
        return $remoteSync;
    }

    /**
     * Logs a message to the Emarsys log file.  Log level 1 will always be logged,
     * log level 2 is only logged if getEmarsysDebug is true.
     * @param $message
     * @param int $logLevel
     */
    public function log($message, $logLevel = 1){
        if($logLevel == 1 || $this->getEmarsysDebug() ){
            Mage::log($message, null, 'aligent_emarsys',true);
        }
    }
}
