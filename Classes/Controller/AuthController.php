<?php
declare(strict_types=1);
namespace WapplerSystems\Samlauth\Controller;



use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\ValidationError;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use WapplerSystems\Samlauth\Service\UserCreator;

/**
 */
class AuthController extends AbstractController
{

    /**
     * @return string
     */
    public function metadataAction()
    {

        $samlSettings = $this->configuration->getSAMLSettings();

        try {
            $settings = new Settings($samlSettings);
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata($metadata);

            return $metadata;

        } catch (Error $e) {
        } catch (\Exception $e) {
        }

        return '';
    }


    public function singleLogoutServiceAction() {



        $samlSettings = $this->configuration->getSAMLSettings();

        $auth = $this->_getSAMLAuth();

        $auth->processSLO();


    }


    public function assertionConsumerServiceAction() {


        /*
        if ($customerSession->isLoggedIn()) {
            $this->_redirect($helper->getUrl('/'));
            return;
        }*/


        


        return;


            $auth = new Auth($this->configuration->getSAMLSettings());
            $auth->processResponse();
            $errors = $auth->getErrors();
            if (!empty($errors)) {
                $errorMsg = "Error at the ACS Endpoint.<br>".implode(', ', $errors);
                /*
                $reason = $auth->getLastErrorReason();
                if ($debug && isset($reason) && !empty($reason)) {
                    $errorMsg .= '<br><br>Reason: '.$reason;
                }
                $this->_processError($errorMsg);
                */
                return;
            }
            if (!$auth->isAuthenticated()) {
                //$this->_processError("ACS Process failed");
                return;
            }

            //DebugUtility::debug($auth->getAttributes());
            //exit();

        /** @var UserCreator $userCreator */
        $userCreator = $this->objectManager->get(UserCreator::class);

        /** @var FrontendUserAuthentication $frontendUserAuth */
        $frontendUserAuth = $this->objectManager->get(FrontendUserAuthentication::class);

        $frontendUser = $userCreator->updateOrCreate($auth->getAttributes(), ['user_folder' => 14]);
        if ($frontendUser) {
            $userRecord = $frontendUserAuth->getRawUserByUid($frontendUser->getUid());

            $sessionData = $frontendUserAuth->createUserSession($userRecord);

        }




    }




    /**
     * Try log and redirect properly
     *
     */
    private function tryLogAndRedirect($customer, $customerSession, $auth, $urlToGo)
    {
        if (isset($customer)) {
            $this->registerCustomerSession($customerSession, $auth, $customer);

            $relayState = $this->getRequest()->getPost('RelayState');
            if (!empty($relayState) && $urlToGo == '/') {
                // Expects as $urlToGo an URL
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setUrl($relayState);
            } else {
                // Expects as $urlToGo a path
                $helper = $this->_getHelper();
                return $this->_redirect($helper->getUrl($urlToGo));
            }
        } else {
            $errorMsg = "SAML plugin failed trying to process the SSO. Review the Attribute Mapping section";
            $this->_processError($errorMsg);
            return;
        }
    }


    /**
     * Process SAML Attributes
     *
     */
    public function processAttrs(Auth $auth, $useCustomAttr = false)
    {
        $customerData = [
            'username' => '',
            'email' => '',
            'firstname' => '',
            'lastname' => '',
            'groupid' => ''
        ];

        $attrs = $auth->getAttributes();

        if (empty($attrs)) {
            if (!$useCustomAttr) {
                $customerData['email'] = $auth->getNameId();
            } else {
                $helper = $this->_getHelper();
                $customAttrIdentifier =  $helper->getConfig('pitbulk_saml2_customer/custom_field_mapping/custom_attribute_mapping');
                if (!empty($customAttrIdentifier)) {
                    $customerData['custom_attr'] = [
                        $customAttrIdentifier => $auth->getNameId()
                    ];
                }
            }
        } else {
            $mapping = $this->getAttrMapping();
            foreach (['username', 'email', 'firstname', 'lastname'] as $key) {
                if (!empty($mapping[$key]) && isset($attrs[$mapping[$key]])
                    && !empty($attrs[$mapping[$key]][0])) {
                    $customerData[$key] = $attrs[$mapping[$key]][0];
                }
            }

            $customerData = $this->addGroupData($customerData, $attrs, $mapping);

            $customerData = $this->addAddressData($customerData, $attrs, $mapping);

            $customerData = $this->addCustomAttributesData($customerData, $attrs);

            // If was not able to get the email by mapping,
            // assign then the NameId if it contains an @
            if (!isset($userData['email']) || empty($userData['email'])) {
                $nameId = $auth->getNameId();
                if (strpos($nameId, "@") !== false) {
                    $customerData['email'] = $nameId;
                }
            }
        }

        return $customerData;
    }


}
