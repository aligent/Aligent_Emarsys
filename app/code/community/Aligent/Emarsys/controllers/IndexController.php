<?php

class Aligent_Emarsys_IndexController extends Mage_Core_Controller_Front_Action {

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
        $oResponse = $this->getResponse();
        $oResponse->setBody('{"failure": true}');

        if($this->_validateFormKey()) {
            $params = $this->getRequest()->getParams();
            $email = $params['email'];
            $firstname = $params['firstname'];
            $lastname = $params['lastname'];
            $dob = $params['dobYY'] . '-' . $params['dobMM'] . '-' . $params['dobDD'];
            if (Zend_Validate::is($email, 'EmailAddress')) {
                if($this->isSubscribed($email)){
                    $oResponse->setBody(json_encode(array('failure'=>true, 'message'=>'Email is already registered. Please use a different email.', 'input'=>$params)));
                }else{
                    /** @var $newsSub Mage_Newsletter_Model_Subscriber */
                    $newsSub = Mage::getModel('newsletter/subscriber');
                    $oResponse->setBody(json_encode(array('failure'=>true, 'input2'=>$params)));

                    Mage::register('emarsys_newsletter_ignore', true);
                    $newsSub->subscribe($email);
                    Mage::unregister('emarsys_newsletter_ignore');
                    if(Mage::helper('aligent_emarsys')->isEnabled()) {
                        /** @var $emHelper Aligent_Emarsys_Helper_Emarsys */
                        $emHelper = Mage::helper('aligent_emarsys/emarsys');
                        $sub = $emHelper->addSubscriber($newsSub->getId(), $firstname, $lastname, $email, $dob);
                        if($sub){
                            $oResponse->setBody(json_encode(array('success'=>true, 'sub_id'=>$newsSub->getId(), 'result'=>$sub->getData())));
                        }else{
                            $oResponse->setBody(json_encode(array('failure'=>true, 'message'=>'Unexpected failure')));
                        }
                    }else{
                        $oResponse->setBody('{"success": true}');
                    }

                }
            }else{
                $oResponse->setBody(json_encode(array('failure'=>true, 'message'=>'Invalid email address', 'input'=>$params)));
            }
        }

        $oResponse->setHeader('Content-type', 'application/json');
    }

    public function emarsyscallbackAction(){
        /** @var $helper Aligent_Emarsys_Helper_Data  */
        $helper = Mage::helper('aligent_emarsys');
        /** @var $emarsysHelper Aligent_Emarsys_Helper_Emarsys l*/
        $emarsysHelper = Mage::helper('aligent_emarsys/emarsys');

        $raw= $this->getRequest()->getRawBody();

        $result = json_decode($raw);
        if($result){
            $emailField = $emarsysHelper->getEmailField();
            $subscribeField = $emarsysHelper->getSubscriptionField();
            $emClient = $emarsysHelper->getClient();
            $results = $emClient->getExportFile($result->id);

            // Disable the observer in our module so we don't end up in a nice little loop.
            Mage::register('emarsys_newsletter_ignore', true);
            if($results->getReplyCode()==0){
                $rows = $results->getData();
                foreach($rows as $row){
                    $subscriber = $helper->getEmailSubscriber($row[$emailField]);
                    $emarsysStatus = $emarsysHelper->unampSubscriptionValue($row[$subscribeField]);
                    $currentStatus = $subscriber->isSubscribed();
                    if($currentStatus != $emarsysStatus || !$subscriber->getSubscriberId()) {
                        if(!$subscriber->getSubscriberId()){
                            $subscriber = Mage::getModel('newsletter/subscriber');
                            $subscriber->setSubscriberEmail($row[$emailField]);
                        }
                        if ($emarsysStatus) {
                            $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
                        }else{
                            $subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                        }
                        $subscriber->save();
                    }
                }
            }
        }
    }

    protected function isSubscribed($email){
        /** @var $newsSub Mage_Newsletter_Model_Subscriber */
        $newsSub = Mage::getModel('newsletter/subscriber');
        $newsSub->loadByEmail($email);
        return ($newsSub->getId() && $newsSub->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
    }

}
