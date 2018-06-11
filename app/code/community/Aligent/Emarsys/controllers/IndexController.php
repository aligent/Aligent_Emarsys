<?php

class Aligent_Emarsys_IndexController extends Mage_Core_Controller_Front_Action {
    /** @var Aligent_Emarsys_Helper_Emarsys */
    protected $_emHelper = null;

    public function preDispatch()
    {
        $this->getLayout()->setArea($this->_currentArea);
        $this->setFlag('', self::FLAG_NO_START_SESSION, true); // Do not start standard session
        $this->setFlag('', self::FLAG_NO_PRE_DISPATCH, true);
        $this->setFlag('', self::FLAG_NO_POST_DISPATCH, true);

        return parent::preDispatch();
    }

    /**
     * @return Aligent_Emarsys_Helper_Emarsys
     */
    public function emarsysHelper(){
        if($this->_emHelper === null){
            $this->_emHelper = Mage::helper('aligent_emarsys/emarsys');
        }
        return $this->_emHelper;
    }

    /**
     * AJAX request to set the cookie.
     * Used mainly for varnish cached hits when the cookie is not set.
     */
    public function cookieupdateAction() {
        $oResponse = $this->getResponse();
        $oResponse->setBody('{"failure": true}');

        if(Mage::helper('aligent_emarsys')->isEnabled()) {
            Mage::helper('aligent_emarsys')->updateCookieFromQuote();

            $oResponse->setBody('{"success": true}');
        }

        $oResponse->setHeader('Content-type', 'application/json');
    }

    /**
     * AJAX request to add an email to the cookie.
     */
    public function newslettersubscribeAction() {
        $contactEmail = Mage::getStoreConfig('trans_email/ident_support/email');
        $oResponse = $this->getResponse();
        $oResponse->setBody(json_encode(
            array(
                'success' => false,
                'message' => "Oops. It looks like there has been a problem with your subscription. 
                    Please try to clear your cookies (Ctrl+Shift+Delete) or email us at <a href=\"mailto:$contactEmail\">$contactEmail</a>"
            )

        ));

        if($this->_validateFormKey()) {
            $params = $this->getRequest()->getParams();
            $email = $params['email'];
            $firstname = $params['firstname'];
            $lastname = $params['lastname'];
            $country = '';

            $yy = isset($params['dobYY']) ? $params['dobYY'] : null;
            $mm = isset($params['dobMM']) ? $params['dobMM'] : null;
            $dd = isset($params['dobDD']) ? $params['dobDD'] : null;
            if ($yy && $mm && $dd) {
                $dob = $yy . '-' . $mm . '-' . $dd;
            }else{
                $dob = null;
            }
            $gender = isset($params['gender']) ? $params['gender'] : null;
            if (Zend_Validate::is($email, 'EmailAddress')) {
                if($this->isSubscribed($email)){
                    $oResponse->setBody(json_encode(array('success'=>false, 'message'=> $this->__('Email is already registered. Please use a different email.'), 'input'=>$params)));
                }else{
                    /** @var $newsSub Mage_Newsletter_Model_Subscriber */
                    $oResponse->setBody(json_encode(array('success'=>false, 'input2'=>$params)));

                    Mage::helper('aligent_emarsys')->startEmarsysNewsletterIgnore();
                    $newsSub = Mage::getModel('newsletter/subscriber')->setStore(Mage::app()->getStore());
                    $newsSub->subscribe($email);
                    Mage::helper('aligent_emarsys')->endEmarsysNewsletterIgnore();

                    if(Mage::helper('aligent_emarsys')->isEnabled()) {
                        /** @var $emHelper Aligent_Emarsys_Helper_Emarsys */
                        $emHelper = Mage::helper('aligent_emarsys/emarsys');
                        $remoteSync = Mage::helper('aligent_emarsys')->ensureNewsletterSyncRecord( $newsSub->getId(), false, true );
                        // Attempt to send the data to Emarsys
                        $sub = $emHelper->addSubscriber($remoteSync->getId(), $firstname, $lastname, $email, $dob, $gender, $country);
                        if($sub && $sub->getData()){
                            $remoteSync->setEmarsysId($sub->getData()['id']);
                            $remoteSync->save();
                            $oResponse->setBody(json_encode(array('success'=>true, 'sub_id'=>$newsSub->getId(), 'result'=>$sub)));
                        }else{
                            Mage::helper('aligent_emarsys')->log("Error Response: " . $sub->getReplyText());
                            $oResponse->setBody(json_encode(array('success'=>false, 'message'=> $this->__('Unexpected failure'))));
                        }
                    } else{
                        $oResponse->setBody('{"success": true}');
                    }

                }
            }else{
                $oResponse->setBody(json_encode(array('success'=>false, 'message'=> $this->__('Invalid email address'), 'input'=>$params)));
            }
        }
        $oResponse->setHeader('Content-type', 'application/json');
    }


    public function emarsyscallbackAction(){
        $raw= $this->getRequest()->getRawBody();
        $result = json_decode($raw);
        $helper = Mage::helper('aligent_emarsys');

        if($result) {
            $emClient = $this->emarsysHelper()->getClient();
            $results = $emClient->getExportFile($result->id);
            // Disable the observer in our module so we don't end up in a nice little loop.
            $helper->startEmarsysNewsletterIgnore();
            if($results->getReplyCode()==0){
                $rows = $results->getData();
                $helper->log("Begining importing Emarsys rows, " . sizeof($rows));
                foreach($rows as $row){
                    $theRow = $emClient->parseRawRow($row);
                    $helper->log("Import " . $theRow->serialize());
                    $this->syncEmarsysRow( $theRow );
                }
                $helper->log("Finished importing Emarsys rows");
            }
            $helper->endEmarsysNewsletterIgnore();
        }
    }

    /**
     * @param $row Aligent_Emarsys_Model_EmarsysRecord
     */
    public function syncEmarsysRow($row){
        $stores = Mage::app()->getStores();

        /** @var $helper Aligent_Emarsys_Helper_Data  */
        $helper = Mage::helper('aligent_emarsys');

        $syncRecord = $helper->ensureEmailSyncRecord(
            $row->getEmail(),
            $row->getFirstName(),
            $row->getLastName(),
            $row->getGender(),
            $row->getDOB(),
            $row->getCountry()
        );

        foreach($stores as $store){
            if(!$helper->syncInStore($store->getId())) continue;
            $subscriber = $helper->getEmailSubscriber( $row->getEmail(), $store->getId() );
            $customer = Mage::getModel('customer/customer')->setStore(Mage::app()->getStore($store->getId()))->loadByEmail($row->getEmail());

            if($customer->getId() && !$subscriber->getSubscriberId()) $subscriber =$helper->getCustomerSubscriber($customer);

            if(!$subscriber->getSubscriberId()){
                $subscriber = $helper->createEmailSubscription($store->getId(), $row->getEmail());
                $subscriber->setCustomerId($customer->getId());
                $syncRecord->linkSubscriber($subscriber->getId());
            }
            $subscriber->setSubscriberStatus( $row->getSubscriptionStatus() );
            $subscriber->save();

            $customer = $subscriber->getCustomer();
            if($customer){
                if($helper->shouldSyncEmarsysFirstnameField()) {
                    $customer->setFirstname($row->getFirstName());
                }

                if($helper->shouldSyncEmarsysLastnameField()){
                    $customer->setLastname($row->getLastName());
                }

                if($helper->shouldSyncEmarsysGenderField()){
                    $customer->setGender($row->getGender());
                }

                if($helper->shouldSyncEmarsysDobField()){
                    $customer->setDob($row->getDOB());
                }
                $customer->save();
            }

        }

        if($helper->shouldSyncEmarsysHarmonyIdField()){
            $syncRecord->setHarmonyId( $row->getHarmonyId() );
        }

        if($helper->shouldSyncEmarsysDobField()){
            $syncRecord->setDob( $row->getDOB() );
        }

        if($helper->shouldSyncEmarsysFirstnameField()){
            $syncRecord->setFirstname( $row->getFirstName() );
        }

        if($helper->shouldSyncEmarsysLastnameField()){
            $syncRecord->setLastname( $row->getLastName() );
        }

        if($helper->shouldSyncEmarsysGenderField()){
            $syncRecord->setGender( $row->getGender() );
        }

        if($helper->shouldSyncEmarsysCountryField()){
            $syncRecord->setCountry( $row->getCountry() );
        }
        $syncRecord->setEmarsysId( $row->getId() );
        $syncRecord->setHarmonySyncDirty(true);
        $syncRecord->setEmarsysSyncDirty(false);
        $syncRecord->save();
    }

    protected function isSubscribed($email){
        /** @var $newsSub Mage_Newsletter_Model_Subscriber */
        $newsSub = Mage::getModel('newsletter/subscriber');
        $newsSub->loadByEmail($email);
        return ($newsSub->getId() && $newsSub->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
    }

}
