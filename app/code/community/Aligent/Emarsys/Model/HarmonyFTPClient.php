<?php
use FtpClient\FtpClient;

class Aligent_Emarsys_Model_HarmonyFTPClient {
    /** @var Aligent_Emarsys_Helper_Data */
    protected $_helper = null;
    /** @var string $_host */
    protected $_host;
    /** @var string $_port */
    protected $_port;
    /** @var string $_user */
    protected $_user;
    /** @var string $_pass */
    protected $_pass;
    /** @var string $_exportDir */
    protected $_exportDir;
    /** @var bool $_pasv */
    protected $_pasv;
    /** @var bool $_sFTP */
    protected $_sFTP;
    /** @var Varien_Io_Sftp|FtpClient $_actualClient  */
    protected $_actualClient = null;
    /** @var int $_timeout */
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

    /**
     * Connects and logs in to the FTP/sFTP server (settings dependant).
     */
    public function connect(){
        if(!$this->_actualClient) $this->_createClient();
        $args = array(
            'host' => $this->_host,
            'username' => $this->_user,
            'password' => $this->_pass,
            'passive' => $this->_pasv
        );
        if($this->_sFTP){
            return $this->_actualClient->open($args);
        }else{
            try{
                $this->_actualClient->connect($args);
            }catch(\FtpClient\FtpException $e){
                return false;
            }
        }
    }

    /**
     * Changes the current directory on the remote
     * @param $dir
     * @return mixed
     */
    public function chdir($dir){
        return $this->_sFTP ? $this->_actualClient->cd($dir) : $this->_actualClient->chdir($dir);
    }

    /**
     * Creates the remote file $fileName from the contents of
     * $fileContents
     *
     * @param $fileName
     * @param $fileContents
     * @return mixed
     */
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

    /**
     * Shortcut to create the remote file $fileName in the configured remote
     * export directory, with the contents of $fileContents
     *
     * @param $fileName
     * @param $fileContents
     * @return mixed
     */
    public function putExportFromString($fileName, $fileContents){
        $this->chdir($this->_exportDir);
        return $this->putFromString($fileName, $fileContents);
    }

    /**
     * Creates the actual underlying client, based on settings for
     * FTP or sFTP
     */
    protected function _createClient(){
        $this->_actualClient = $this->_sFTP ? new Varien_Io_Sftp() : new FtpClient();
    }
}