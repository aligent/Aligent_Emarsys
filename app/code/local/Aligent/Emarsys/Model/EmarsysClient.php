<?php

use Snowcap\Emarsys\HttpClient;

class Aligent_Emarsys_Model_EmarsysClient extends \Snowcap\Emarsys\Client {
    private $_labelMap = null;

    /**
     * @var Snowcap\Emarsys\Response
     */
    private $_remoteFields = null;

    /**
     * @var string
     */
    private $baseUrl = 'https://api.emarsys.net/api/v2/';

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var HttpClient
     */
    private $client;

    public static function create(){
        /** @var Aligent_Emarsys_Helper_Data $helper */
        $helper = Mage::helper('aligent_emarsys');
        $emarsysUser = $helper->getEmarsysAPIUser();
        $emarsysSecret = $helper->getEmarsysAPISecret();
        return new Aligent_Emarsys_Model_EmarsysClient( new Snowcap\Emarsys\CurlClient() , $emarsysUser, $emarsysSecret);
    }

    public function __construct(HttpClient $client, $username, $secret, $baseUrl = null, array $fieldsMap = array(), array $choicesMap = array()){
        $this->username = $username;
        $this->secret = $secret;
        $this->client = $client;

        if (null !== $baseUrl) {
            $this->baseUrl = $baseUrl;
        }

        parent::__construct($client, $username, $secret, $baseUrl, $fieldsMap, $choicesMap);
    }

    public function exportChangesSince($dateString, $callback = null){
        /** @var Aligent_Emarsys_Helper_Emarsys $emarsysHelper */
        $emarsysHelper = Mage::helper('aligent_emarsys/emarsys');
        $fields = [ $emarsysHelper->getEmailField(),
                $emarsysHelper->getSubscriptionField(),
                $emarsysHelper->getFirstnameField(),
                $emarsysHelper->getLastnameField(),
                $emarsysHelper->getDobField() ];
        $data = array(
            'time_range' => [date('Y-m-d', strtotime($dateString)), date('Y-m-d')],
            'contact_fields' => $fields,
            'distribution_method' => 'local', 'origin' => 'all', 'origin_id' => 0 );
        if($callback) $data['notification_url'] = $callback;
        $results = $this->getContactChanges($data);
        if($callback) return $results;
        if ($results->getReplyCode() == 0) {
            $jobId = $results->getData()['id'];
            sleep(10);
            $seconds = 10;
            $results = $this->getExportStatus([$jobId]);
            while ($results->getReplyCode() == 0 && $seconds < 60 && $results->getData()['status'] != 'done') {
                set_time_limit(0);
                sleep(10);
                $seconds += 10;
                $results = $this->getExportStatus([$jobId]);
            }
            if ($results->getData()['status'] == 'done') {
                return $this->getExportFile($jobId);
            }
        }else{
            return $results;
        }
        return $results;

    }

    public function getExportStatus(array $data){
        $id = 0;
        foreach($data as $key=>$id) break;
        return $this->send(HttpClient::GET, 'export/' . $id);
    }

    public function getExportFile($id){
        $headers = array('Content-Type: application/json', 'X-WSSE: ' . $this->getAuthenticationSignature());
        $uri = $this->baseUrl . "export/$id/data/?offset=0&limit=1000000";
        try {
            $responseJson = $this->client->send(HttpClient::GET, $uri, $headers, array());
            $responseJson = $this->parseResponseCSV($responseJson);
            return new Snowcap\Emarsys\Response(array('replyCode'=>0,'replyText'=>'OK','data'=> $responseJson));
        } catch (\Exception $e) {
            throw new \Snowcap\Emarsys\Exception\ServerException($e->getMessage());
        }
    }

    /**
     * The only bit of the API that doesn't return JSON data, is data exports.
     * This takes the CSV data string and returns a normalised array using
     * Emarsys field IDs as the row's field keys.
     *
     * @param $csvData
     * @return array
     */
    protected function parseResponseCSV($csvData){
        $Headers = array();
        $DataRows = array();
        $Data = str_getcsv($csvData, "\n"); //parse the rows

        // Still here?  We need to make a header map.
        $HeaderRow = str_getcsv($Data[0], ",");
        foreach($HeaderRow as $fieldName){
            try {
                $Headers[] = $this->getFieldByLabel($fieldName);
            }catch(\Exception $e){
                $Headers[] = 'error_' . $fieldName;
            }
        }

        // Now go through the rest of the data rows and key them by field ID
        for($i=1; $i < sizeof($Data); $i++){
            $rawRow = str_getcsv($Data[$i], ","); //parse the items in rows
            $actualRow = array();
            foreach($rawRow as $key=>$value){
                $actualRow[$Headers[$key]] = $value;
            }
            $DataRows[] = $actualRow;
        }
        return $DataRows;
    }

    /**
     * This is overriden/extended in order to cache the result for the lifetime
     * of the client object.
     *
     * @return \Snowcap\Emarsys\Response
     */
    public function getFields()
    {
        if ($this->_remoteFields == null) {
            $this->_remoteFields = parent::getFields();
            $this->_labelMap = array();
            foreach($this->_remoteFields->getData() as $field){
                $this->_labelMap[$field['name']] = $field['id'];
            }
        }
        return $this->_remoteFields;
    }

    /**
     * Maps an Emarsys field label to the Emarsys field ID.  This is used when normalizing
     * downloaded CSV files.
     *
     * @param $label
     * @return mixed
     * @throws \Snowcap\Emarsys\Exception\ClientException
     */
    public function getFieldByLabel($label){
        // Call this to ensure that label map is populated.
        $this->getFields();

        if(isset($this->_labelMap[$label])) return $this->_labelMap[$label];

        // Still here?  Throw an exception
        $e = new Snowcap\Emarsys\Exception\ClientException("Invalid field $label");
        throw $e;
    }

    /**
     * Generate X-WSSE signature used to authenticate
     *
     * @return string
     */
    private function getAuthenticationSignature()
    {
        // the current time encoded as an ISO 8601 date string
        $created = new \DateTime();
        $iso8601 = $created->format(\DateTime::ISO8601);
        // the md5 of a random string . e.g. a timestamp
        $nonce = md5($created->modify('next friday')->getTimestamp());
        // The algorithm to generate the digest is as follows:
        // Concatenate: Nonce + Created + Secret
        // Hash the result using the SHA1 algorithm
        // Encode the result to base64
        $digest = base64_encode(sha1($nonce . $iso8601 . $this->secret));

        $signature = sprintf(
            'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $this->username,
            $digest,
            $nonce,
            $iso8601
        );

        return $signature;
    }

}
