<?php

class Aligent_Emarsys_Model_PeclSFTPClient {
    protected $_host = null;
    protected $_username = null;
    protected $_password = null;
    protected $_connection = null;
    protected $_port = 22;
    protected $_sftp = null;
    protected $_cwd = "";
    protected $_basePath = ".";

    public function open($args)
    {
        $this->_host = isset($args['host']) ? $args['host'] : null;
        $this->_username = isset($args['username']) ? $args['username'] : null;
        $this->_password = isset($args['password']) ? $args['password'] : null;
        $this->_port = isset($args['port']) ? $args['port'] : $this->_port;
        $this->_validateConnectionSettings();

        $this->_connection = ssh2_connect($this->_host, $this->_port);
        if ($this->_connection === false) {
            throw new \Exception("Unable to connect");
        }

        if (ssh2_auth_password($this->_connection, $this->_username, $this->_password)) {
            $this->_sftp = ssh2_sftp($this->_connection);
            $this->_basePath = $this->_execCmd("pwd");
        } else {
            throw new \Exception("Unable to authenticate");
        }
    }

    public function pwd(){
        return $this->_execCmd("cd " . $this->_basePath . $this->_cwd . " && pwd");
    }

    public function cd($dir){
        $realdir = ssh2_sftp_realpath($this->_sftp, $this->_basePath . $this->_cwd . "/$dir/");
        $this->_cwd = str_replace($this->_basePath, "", $realdir);
    }

    public function ls(){
        $cmd = "ls " . $this->_basePath . $this->_cwd;
        $data = $this->_execCmd($cmd);
        $files = preg_split("/\n/", $data);
        return $files;
    }

    public function write($remoteFile, $fileContents){
        $fh = fopen("ssh2.sftp://" . intval($this->_sftp) . $this->_basePath . $this->_cwd . "/$remoteFile", 'w+');
        fwrite($fh, $fileContents);
        fclose($fh);
    }

    public function download($remoteFile, $localFile){
        // Remote stream
        if (!$remoteStream = @fopen("ssh2.sftp://" . intval($this->_sftp) . "/" . $this->_basePath . $this->_cwd . "/$remoteFile", 'r')) {
            throw new Exception("Unable to open remote file: " . $this->_basePath . $this->_cwd . "/$remoteFile");
        }

        // Local stream
        if (!$localStream = @fopen($localFile, 'w')) {
            throw new Exception("Unable to open local file for writing: $localFile");
        }

        // Write from our remote stream to our local stream
        $read = 0;
        $fileSize = filesize("ssh2.sftp://" . intval($this->_sftp) . "/" . $this->_basePath . $this->_cwd . "/$remoteFile");
        while ($read < $fileSize && ($buffer = fread($remoteStream, $fileSize - $read))) {
            // Increase our bytes read
            $read += strlen($buffer);
            // Write to our local file
            if (fwrite($localStream, $buffer) === FALSE) {
                throw new Exception("Unable to write to local file: $localFile");
            }
        }

        // Close our streams
        fclose($localStream);
        fclose($remoteStream);
    }

    public function delete($remoteFile){
        $this->_execCmd("unlink " . $this->_basePath . $this->_cwd . "/$remoteFile");
    }

    protected function _execCmd($cmd)
    {
        $stream = ssh2_exec($this->_connection, $cmd);
        stream_set_blocking($stream, 1);
        $data = stream_get_contents($stream);
        return rtrim($data);
    }

    protected function _validateConnectionSettings(){
        if ($this->_host == null) {
            throw new \Exception("Host must be specified");
        }

        if ($this->_username == null) {
            throw new \Exception("Username must be specified");
        }

        if ($this->_password == null) {
            throw new \Exception("Password must be specified");
        }

    }
}
