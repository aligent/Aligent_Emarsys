<?php
class Aligent_Emarsys_Helper_Data extends Mage_Core_Helper_Abstract {
    protected $_feedGiftcardPrice = array();
    protected $_feedStockFromSimple = null;
    protected $_includeSimpleParents = null;
    protected $_includeDisabled = null;
    protected $_harmonyDumpFile = null;
    protected $_harmonyExportLive = null;

    protected $_cookieName = null;
    protected $_enabled = null;
    protected $_merchantId = null;
    protected $_scarabJsUrl = null;
    protected $_sendEmail = null;
    protected $_isTestMode = null;
    protected $_sendParentSku = null;
    protected $_useStoreSku = null;
    protected $_sendWebsiteCode = null;
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
    protected $_harmonyIdField = array();
    protected $_harmonyWebAgent = null;

    protected $_emarsysDebug = null;
    protected $_emarsysAPIUser = null;
    protected $_emarsysAPISecret = null;
    protected $_emarsysSubscriptionField = null;
    protected $_emarsysVoucherField = null;
    protected $_emarsysSyncHarmonyId = null;
    protected $_emarsysSyncFirstname = null;
    protected $_emarsysSyncLastname = null;
    protected $_emarsysSyncGender = null;
    protected $_emarsysSyncDOB = null;
    protected $_emarsysSyncCountry = null;
    protected $_emarsysDobField = null;

    protected $_emarsysChunkSize = null;
    protected $_emarsysChangePeriod = null;

    const XML_FEED_GIFTCARD_PRICE = 'aligent_emarsys/feed/default_giftcard_price';
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
    const XML_EMARSYS_USE_STORE_SKU_PATH = 'aligent_emarsys/settings/store_sku';
    const XML_EMARSYS_SEND_WEBSITE_CODE_PATH = 'aligent_emarsys/settings/send_website_code';

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
    const XML_HARMONY_CUSTOMER_MODE = 'aligent_emarsys/harmony_settings/harmony_customer_export_mode';

    const XML_EMARSYS_API_DEBUG_MODE = 'aligent_emarsys/emarsys_api_settings/emarsys_debug';
    const XML_EMARSYS_API_USER = 'aligent_emarsys/emarsys_api_settings/emarsys_username';
    const XML_EMARSYS_API_SECRET = 'aligent_emarsys/emarsys_api_settings/emarsys_secret';
    const XML_EMARSYS_API_SUBSCRIPTION_FIELD = 'aligent_emarsys/emarsys_api_settings/emarsys_subscription_field_id';
    const XML_EMARSYS_API_VOUCHER_FIELD = 'aligent_emarsys/emarsys_api_settings/emarsys_voucher_field_id';
    const XML_EMARSYS_API_SYNC_FIRSTNAME = 'aligent_emarsys/emarsys_api_settings/emarsys_sync_firstname';
    const XML_EMARSYS_API_SYNC_HARMONYID = 'aligent_emarsys/emarsys_api_settings/emarsys_sync_harmonyid';
    const XML_EMARSYS_API_SYNC_LASTNAME = 'aligent_emarsys/emarsys_api_settings/emarsys_sync_lastname';
    const XML_EMARSYS_API_SYNC_GENDER = 'aligent_emarsys/emarsys_api_settings/emarsys_sync_gender';
    const XML_EMARSYS_API_SYNC_DOB = 'aligent_emarsys/emarsys_api_settings/emarsys_sync_dob';
    const XML_EMARSYS_API_SYNC_COUNTRY = 'aligent_emarsys/emarsys_api_settings/emarsys_sync_country';
    const XML_EMARSYS_API_DOB_FIELD = 'aligent_emarsys/emarsys_api_settings/emarsys_dob_field';
    const XML_EMARSYS_API_HARMONY_ID_FIELD = 'aligent_emarsys/emarsys_api_settings/harmony_id_field';
    const XML_EMARSYS_API_CHUNK_SIZE = 'aligent_emarsys/emarsys_api_settings/emarsys_chunk_size';
    const XML_EMARSYS_API_CHANGE_PERIOD = 'aligent_emarsys/emarsys_api_settings/emarsys_changes_period';

    public function getHarmonyWebAgent(){
        if($this->_harmonyWebAgent === null){
            $this->_harmonyWebAgent = Mage::getStoreConfig(self::XML_EMARSYS_HARMONY_WEB_AGENT);
        }
        return $this->_harmonyWebAgent;
    }

    public function getEmarsysChunkSize(){
        if($this->_emarsysChunkSize === null){
            $this->_emarsysChunkSize = Mage::getStoreConfig( self::XML_EMARSYS_API_CHUNK_SIZE );
        }
        return $this->_emarsysChunkSize;
    }

    public function getGiftcardDefaultPrice($oStore = null){
        $storeId = $oStore;
        if (is_object($oStore)) {
            $storeId = $oStore->getId();
        }
        if($storeId === null){
            $storeId = Mage::app()->getStore()->getId();
        }

        if(!isset($this->_feedGiftcardPrice[$storeId])){
            $this->_feedGiftcardPrice[$storeId] = Mage::getStoreConfig(self::XML_FEED_GIFTCARD_PRICE, $storeId);
        }
        return $this->_feedGiftcardPrice[$storeId];
    }

    /**
     * Retrieve the change period (in hours) to check for changed Emarsys records
     * on Emarsys import.
     * @return int
     */
    public function getEmarsysChangePeriod(){
        if($this->_emarsysChangePeriod === null){
            $this->_emarsysChangePeriod = Mage::getStoreConfig(self::XML_EMARSYS_API_CHANGE_PERIOD);
        }
        return $this->_emarsysChangePeriod;
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

    public function getHarmonyCustomerExportLive(){
        if($this->_harmonyExportLive === null){
            $this->_harmonyExportLive = Mage::getStoreConfigFlag(self::XML_HARMONY_CUSTOMER_MODE);
        }
        return $this->_harmonyExportLive;
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
     * Should sync harmony ID field from Emarsys into Aligent table
     * @return bool
     */
    public function shouldSyncEmarsysHarmonyIdField() {
        if($this->_emarsysSyncHarmonyId === null){
            $this->_emarsysSyncHarmonyId = Mage::getStoreConfigFlag(self::XML_EMARSYS_API_SYNC_HARMONYID);
        }
        return $this->_emarsysSyncHarmonyId;
    }

    /**
     * Should sync country field from Emarsys into Aligent table.
     * @return bool
     */
    public function shouldSyncEmarsysCountryField(){
        if($this->_emarsysSyncCountry === null){
            $this->_emarsysSyncCountry = Mage::getStoreConfigFlag(self::XML_EMARSYS_API_SYNC_COUNTRY);
        }
    }

    /**
     * Get the Harmony ID field to populate in Emarsys, if specified.
     * @param null $store
     * @return mixed|null
     */
    public function getHarmonyIdField($store = null){
        $storeId = $store;
        if (is_object($store)) {
            $storeId = $store->getId();
        }

        if (!isset($this->_harmonyIdField[$storeId])) {
            $this->_harmonyIdField[$storeId] = Mage::getStoreConfig(self::XML_EMARSYS_API_HARMONY_ID_FIELD, $store);
        }

        return $this->_harmonyIdField[$storeId];
    }

    /**
     * Should sync firstname field from Emarsys into Aligent table.
     * @return string
     */
    public function shouldSyncEmarsysFirstnameField(){
        if($this->_emarsysSyncFirstname === null){
            $this->_emarsysSyncFirstname = Mage::getStoreConfigFlag(self::XML_EMARSYS_API_SYNC_FIRSTNAME);
        }
        return $this->_emarsysSyncFirstname;
    }

    /**
     * Should sync lastname field from Emarsys into Aligent table.
     * @return string
     */
    public function shouldSyncEmarsysLastnameField(){
        if($this->_emarsysSyncLastname === null){
            $this->_emarsysSyncLastname = Mage::getStoreConfigFlag(self::XML_EMARSYS_API_SYNC_LASTNAME);
        }
        return $this->_emarsysSyncLastname;
    }

    /**
     * Should sync gender field from Emarsys into Aligent table.
     * @return string
     */
    public function shouldSyncEmarsysGenderField(){
        if($this->_emarsysSyncGender === null){
            $this->_emarsysSyncGender = Mage::getStoreConfigFlag(self::XML_EMARSYS_API_SYNC_GENDER);
        }
        return $this->_emarsysSyncGender;
    }

    /**
     * Should sync DOB from Emarsys into Aligent table
     * @return string
     */
    public function shouldSyncEmarsysDobField(){
        if($this->_emarsysSyncDOB === null){
            $this->_emarsysSyncDOB = Mage::getStoreConfigFlag(self::XML_EMARSYS_API_SYNC_DOB);
        }
        return $this->_emarsysSyncDOB;
    }

    /**
     * Get the DOB field to use with Emarsys.  Blank if using default birthDate field
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
     * @param int $storeId Optional store ID to retrieve config for
     *
     * @return string
     */
    public function getEmarsysAPISubscriptionField($storeId = null){
        $this->log("Get emarsys field for store $storeId\n");
        $this->_emarsysSubscriptionField = Mage::getStoreConfig(self::XML_EMARSYS_API_SUBSCRIPTION_FIELD, $storeId);
        if($this->_emarsysSubscriptionField=='-1') $this->_emarsysSubscriptionField = '';
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
     * Should the store code be included in the sku sent to Emarsys from the frontend
     * @return bool
     */
    public function shouldUseStoreSku() {
        if ($this->_useStoreSku === null) {
            $this->_useStoreSku = Mage::getStoreConfigFlag(self::XML_EMARSYS_USE_STORE_SKU_PATH);
        }

        return $this->_useStoreSku;
    }

    /**
     * Should the website code be tagged and sent for each event.
     *
     * @return bool
     */
    public function getSendWebsiteCode() {
        if ($this->_sendWebsiteCode === null) {
            $this->_sendWebsiteCode = Mage::getStoreConfigFlag(self::XML_EMARSYS_SEND_WEBSITE_CODE_PATH);
        }
        return $this->_sendWebsiteCode;
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
            if(Mage::helper('aligent_emarsys')->shouldUseStoreSku() ){
                $tempItem['id'] = Mage::app()->getStore()->getCode() . '_' . $tempItem['id'];
            }

            if ($itemToUse->getProductType() == 'configurable') {
                $product = $itemToUse->getProduct();
                // getSku() will get the child, must grab the data specifically.
                $tempItem['parentid'] = $product->getData('sku');

                if(Mage::helper('aligent_emarsys')->shouldUseStoreSku() ){
                    $tempItem['parentid'] = Mage::app()->getStore()->getCode() . '_' . $tempItem['parentid'];
                }
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

        $dataWrapper = new Varien_Object($cartArray);
        Mage::dispatchEvent('aligent_emarsys_webextend_format_cart', array('cart_array'=>$dataWrapper));
        $cartArray = $dataWrapper->getData();

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
    public function getEmailSubscriber($email, $storeId){
        return Mage::getModel('newsletter/subscriber')->loadByEmail($email, $storeId);
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
     * Ensures a newsletter subscription record exists for this customer in
     * the customer's store scope.
     *
     * @param $customer Mage_Customer_Model_Customer
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function ensureCustomerNewsletter($customer){
        $storeId = $customer->getStore()->getId();

        if(!is_a($customer, 'Mage_Customer_Model_Customer')){
            throw new InvalidArgumentException("Expected Mage_Customer_Model_Customer, passed " . get_class($customer));
        }

        $newsletter = Mage::getModel('newsletter/subscriber')->setStoreId($storeId);
        $this->log("Find newsletter for customer " . $customer->getId());
        $this->log(print_r($newsletter,true));
        $newsletter->load($customer->getId(), 'customer_id');

        if(!$newsletter->getId()){
            $this->log("Can't find.  Try email '" . $customer->getEmail() . "'");
            $newsletter->loadByEmail($customer->getEmail(), $storeId);
        }

        if(!$newsletter->getId()){
            $this->log("Nope.  Create it");
            $this->startEmarsysNewsletterIgnore();
            $newsletter = $this->createSubscription($customer);
            $this->endEmarsysNewsletterIgnore();
        }
        $this->log("Ok, got ID " . $newsletter->getId());

        // Ensure it's hooked to the customer
        if($newsletter->getCustomerId() != $customer->getId()){
            $this->log("Hook " . $customer->getId() . " customer to " . $newsletter->getId() . " subscriber");
            Mage::helper('aligent_emarsys/lightweightDataHelper')->getWriter()->update(
                $newsletter->getResource()->getMainTable(),
                array('customer_id'=>$customer->getId()),
                'subscriber_id=' . $newsletter->getId()
            );
        }
        return $newsletter;

    }

    /**
     * Locate an Aligent_Emarsys_Remote_System_Sync_flags record
     * linked to the given customer_entity_id, or return a new model
     * with the ID set if not found.
     *
     * @param $id int The customer entity id to find
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function findCustomerSyncRecord($id){
        $customer = Mage::getModel('customer/customer')->load($id);
        if(!$customer->getId()){
            throw new InvalidArgumentException("Invalid customer ID passed");
        }
        $newsletter = $this->ensureCustomerNewsletter($customer);

        return $this->ensureNewsletterSyncRecord(
            $newsletter->getId(),
            true,
            true,
            $customer->getFirstname(),
            $customer->getLastName(),
            $customer->getGender(),
            $customer->getDob()
        );
    }

    /**
     * @param $subscriber
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function findNewsletterSyncRecord($subscriber){
        $syncLink = Mage::getModel('aligent_emarsys/aeNewsletters')->load($subscriber->getId(), 'subscriber_id');
        if(!$syncLink->getAeId()){
            $remoteSync = Aligent_Emarsys_Model_RemoteSystemSyncFlags::loadByEmail($subscriber->getSubscriberEmail());
            if($remoteSync){
                $remoteSync->linkSubscriber($subscriber->getId());
                return $remoteSync;
            }else{
                return null;
            }
        }else{
            $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags')->load($syncLink->getAeId());
            return $remoteSync->getId() ? $remoteSync : null;
        }
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
     * @param string $country Customer country to populate the record with
     * @param int $storeId The store ID to use
     *
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function ensureNewsletterSyncRecord($id, $emarsysFlag = true, $harmonyFlag = true, $firstName = null, $lastName = null, $gender = null, $dob = null, $country = null, $storeId = null){
        $bCreateLink = false;

        $storeId = ($storeId===null) ? Mage::app()->getStore()->getId() : $storeId;

        $subscriber = Mage::getModel('newsletter/subscriber')->setStoreId($storeId)->load($id);

        if(!$subscriber->getId()){
            return null;// If we weren't passed a valid newsletter subscriber ID, just bail
        }
        $remoteSync = $this->findNewsletterSyncRecord($subscriber);
        if(!$remoteSync){
            $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags');
            $bCreateLink = true;

        }
        if($harmonyFlag) $remoteSync->setHarmonySyncDirty($harmonyFlag);
        if($emarsysFlag) $remoteSync->setEmarsysSyncDirty($emarsysFlag);
        if($firstName) $remoteSync->setFirstName($firstName);
        if($lastName) $remoteSync->setLastName($lastName);
        if($dob) $remoteSync->setDob($dob);
        if($gender) $remoteSync->setGender($gender);
        if($country) $remoteSync->setCountry($country);
        $remoteSync->setEmail($subscriber->getSubscriberEmail());
        $remoteSync->save();

        if($bCreateLink){
            $remoteSync->linkSubscriber($id);
        }

        return $remoteSync;
    }

    /**
     * @param $email
     * @param null $firstName
     * @param null $lastName
     * @param null $gender
     * @param null $dob
     * @param null $country
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function ensureEmailSyncRecord($email, $firstName = null, $lastName = null, $gender = null, $dob = null, $country = null){
        $remoteSync = Mage::getModel('aligent_emarsys/remoteSystemSyncFlags');
        $remoteSync->load($email, 'email');
        if($remoteSync->getId()) return $remoteSync;

        // Still here?  We'll need to create a bare bones record.
        $remoteSync->setEmail($email);
        $remoteSync->setFirstName($firstName);
        $remoteSync->setLastName($lastName);
        $remoteSync->setGender($gender);
        $remoteSync->setDob($dob);
        $remoteSync->setCountry($country);
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
    public function ensureCustomerSyncRecord($id, $emarsysFlag = true, $harmonyFlag = true, $country = null){
        $remoteSync = $this->findCustomerSyncRecord($id);
        if($harmonyFlag) $remoteSync->setHarmonySyncDirty($harmonyFlag);
        if($emarsysFlag) $remoteSync->setEmarsysSyncDirty($emarsysFlag);
        if($country) $remoteSync->setCountry($country);

        if($harmonyFlag || $emarsysFlag || $country){
            $remoteSync->save();
        }
        return $remoteSync;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return Aligent_Emarsys_Model_RemoteSystemSyncFlags
     */
    public function ensureOrderSyncRecord($order){
        $storeId = $order->getStoreId();

        // If the order contains only virtual products, it may not have a shipping address
        $country = ($order->getShippingAddress()) ? $order->getShippingAddress()->getCountry() : null;

        // Is there a customer ID?
        if($order->getCustomerIsGuest()){
            // Ok, let's try to find a subscriber
            $subscriber = Mage::getModel('newsletter/subscriber');
            $subscriber->loadByEmail($order->getCustomerEmail(), $storeId);
            if(!$subscriber->getId()){
                $this->startEmarsysNewsletterIgnore();
                $subscriber = $this->createEmailSubscription($storeId, $order->getCustomerEmail());
                $this->endEmarsysNewsletterIgnore();
            }
            $remoteSync = $this->ensureNewsletterSyncRecord($subscriber->getId(),
                true,
                true,
                $order->getCustomerFirstname(),
                $order->getCustomerLastname(),
                $order->getCustomerGender(),
                $order->getCustomerDob(),
                $country);
        }else{
            $remoteSync = $this->ensureCustomerSyncRecord($order->getCustomerId(), true, true, $country);
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
            Mage::log($message, null, 'aligent_emarsys.log',true);
        }
    }

    /**
     * Create a store scoped newsletter_subscriber record based on this customer object.
     *
     * @param $customer Mage_Customer_Model_Customer|stdClass
     * @param $lightWeight bool Whether to use a lightweight insert (one off shell/setup scripts ONLY)
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function createSubscription($customer, $lightWeight = false){
        if($lightWeight){
            $storeId = $customer->store_id;

            $subscription = Mage::getModel('newsletter/subscriber');
            $table = $subscription->getResource()->getMainTable();
            $writer = Mage::helper('aligent_emarsys/lightweightDataHelper')->getWriter();

            $data = array(
                'store_id' => $storeId,
                'customer_id' => $customer->entity_id,
                'subscriber_email' => $customer->email,
                'subscriber_status' => Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE
            );
            $writer->insert($table, $data);
            $subscription->loadByEmail($customer->email, $storeId);
        }else{
            if(!is_a($customer, 'Mage_Customer_Model_Customer')){
                throw new InvalidArgumentException("Expected Mage_Customer_Model_Customer, passed " . get_class($customer));
            }
            $storeId = $customer->getStore()->getId();

            $subscription = Mage::getModel('newsletter/subscriber');
            $subscription->setStoreId($storeId);
            $subscription->setCustomerId($customer->getEntityId());
            $subscription->setSubscriberEmail($customer->getEmail());
            $subscription->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE);
            $subscription->save();
        }

        return $subscription;
    }

    /**
     * @param $storeId
     * @param $email
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function createEmailSubscription($storeId, $email){
        // First up, is there a customer in this store scope with that email address?
        $customer = Mage::getModel('customer/customer')->setStore(Mage::app()->getStore($storeId))->loadByEmail($email);
        if($customer->getId()){
            $subscription = $this->createSubscription($customer);
        }else{
            $subscription = Mage::getModel('newsletter/subscriber');
            $subscription->setStoreId($storeId);
            $subscription->setSubscriberEmail($email);
            $subscription->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
            $subscription->save();
        }

        return $subscription;
    }

    public function startEmarsysNewsletterIgnore(){
        Mage::register('emarsys_newsletter_ignore', true, true);
    }

    public function endEmarsysNewsletterIgnore(){
        Mage::unregister('emarsys_newsletter_ignore');
    }
}
