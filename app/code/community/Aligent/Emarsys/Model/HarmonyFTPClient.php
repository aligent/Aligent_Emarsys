<?php
use FtpClient\FtpClient;

class Aligent_Emarsys_Model_HarmonyFTPClient {
    /** @var Aligent_Emarsys_Helper_Data */
    protected $_helper = null;

    protected $_host;

    protected $_port;

    protected $_user;

    protected $_pass;

    protected $_exportDir;

    protected $_pasv;

    protected $_sFTP;

    protected $_actualClient = null;

    protected $_timeout = 15;

    public function __construct(){
        $this->_helper = Mage::helper('aligent_emarsys');
        $this->_host = $this->_helper->getHarmonyFTPServer();
        $this->_port = $this->_helper->getHarmonyFTPPort();
        $this->_user = $this->_helper->getHarmonyFTPUsername();
        $this->_pass = $this->_helper->getHarmonyFTPPassword();
        $this->_pasv = $this->_helper->getHarmonyPasv();
        $this->_sFTP = $this->_helper->getHarmonySFTP();
        $this->_exportDir = $this->_helper->getHarmonyFTPExportDir();
    }

    public function connect(){
        if(!$this->_actualClient) $this->_createClient();
        if($this->_sFTP){
            $this->_actualClient->connect($this->_host, false, $this->_port, $this->_timeout);
            $this->_actualClient->login($this->_user, $this->_pass);
        }else{
            $this->_actualClient->connect(array(
                'host' => $this->_host,
                'username' => $this->_user,
                'password' => $this->_pass,
                'passive' => $this->_pasv
            ));
        }
    }

    public function chdir($dir){
        return $this->_sFTP ? $this->_actualClient->cd($dir) : $this->_actualClient->chdir($dir);
    }

    public function putFromString($fileName, $fileContents){
        if($this->_sFTP){
            $tmpfname = tempnam(sys_get_temp_dir(), 'alg');
            file_put_contents($tmpfname, $fileContents);
            $result = $this->_actualClient->write($fileName, $tmpfname);
            unlink($tmpfname);
            return $result;
        }else{
            return $this->_actualClient->putFromString($fileName, $fileContents);
        }
    }

    public function putExportFromString($fileName, $fileContents){
        $this->chdir($this->_exportDir);
        return $this->putFromString($fileName, $fileContents);
    }

    protected function _createClient(){
        $this->_actualClient = $this->_sFTP ? new Varien_Io_Sftp() : new FtpClient();
    }
}