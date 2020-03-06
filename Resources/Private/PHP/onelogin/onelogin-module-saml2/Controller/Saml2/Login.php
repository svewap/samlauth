<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Controller\Saml2;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;

use Pitbulk\SAML2\Controller\AbstractCustomController;
use Pitbulk\SAML2\Helper\Data;
use Pitbulk\SAML2\Model\AuthnRequestFactory;
use Pitbulk\SAML2\Model\SettingFactory;
use OneLogin\Saml2\Utils;

use Psr\Log\LoggerInterface;

class Login extends AbstractCustomController
{
    private $settingFactory;

    private $authnRequestFactory;

    public function __construct(
        Context $context,
        Session $session,
        Data $helper,
        LoggerInterface $logger,
        FormKey $formKey,
        SettingFactory $settingFactory,
        AuthnRequestFactory $authnRequestFactory
    ) {
            $this->settingFactory = $settingFactory;
            $this->authnRequestFactory = $authnRequestFactory;

            parent::__construct($context, $session, $helper, $logger, $formKey);
    }

    public function execute()
    {
        $helper = $this->_getHelper();
        $customerSession = $this->_getCustomerSession();
        $errorMsg = null;

        $moduleEnabled = $helper->checkEnabledModule();
        if ($moduleEnabled) {
            if (!$customerSession->isLoggedIn()) {
                $idpSSOBinding = $helper->getConfigIdP('sso_binding');
                if ($idpSSOBinding == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
                    $this->postLogin($helper, $customerSession);
                } else {
                    $this->normalLogin($helper, $customerSession);
                }
            } else {
                $errorMsg = "You tried to start a SSO process but you" .
                            " are already logged";
            }
        } else {
            $errorMsg = "You tried to start a SSO process but" .
                        " SAML2 module has disabled status";
        }

        if (isset($errorMsg)) {
            $this->_processError($errorMsg);
        }
    }

    private function normalLogin($helper, $customerSession)
    {
        $auth = $this->_getSAMLAuth();
        if (isset($auth)) {
            $redirectTo = $customerSession->getBeforeAuthUrl();
            if (!isset($redirectTo) || empty($redirectTo) ||
                 strpos($redirectTo, '/customer/account/logoutSuccess') !== false) {
                $redirectTo =  $helper->getUrl('/');
            }
            $auth->login($redirectTo);
        } else {
            $errorMsg = "You tried to start a SSO process but" .
                    " SAML2 module has wrong settings";
        }
    }

    private function postLogin($helper, $customerSession)
    {
        $settingsInfo = $this->_getSAMLSettings();
        if (isset($settingsInfo)) {
            $settings = $this->settingFactory->create(["settings" => $settingsInfo, "spValidationOnly" => true]);

            $authNRequest = $this->authnRequestFactory->create(["settings" => $settings]);
            $authNRequestXML = $authNRequest->getXML();

            if ($settingsInfo['security']['authnRequestsSigned']) {
                $key = $settings->getSPkey();
                $cert = $settings->getSPcert();

                $signatureAlgorithm = $settingsInfo['security']['signatureAlgorithm'];
                $digestAlgorithm = $settingsInfo['security']['digestAlgorithm'];
            
                $signedAuthNRequestXML = Utils::addSign(
                    $authNRequestXML,
                    $key,
                    $cert,
                    $signatureAlgorithm,
                    $digestAlgorithm
                );
            
                $encodedAuthNRequest = base64_encode($signedAuthNRequestXML);
            } else {
                $encodedAuthNRequest = base64_encode($authNRequestXML);
            }

            $params = [
                'SAMLRequest' => $encodedAuthNRequest
            ];

            $redirectTo = $customerSession->getBeforeAuthUrl();
            if (!isset($redirectTo) || empty($redirectTo) ||
                 strpos($redirectTo, '/customer/account/logoutSuccess') !== false) {
                $redirectTo =  $helper->getUrl('/');
            }

            if (!empty($redirectTo)) {
                $params['RelayState'] = $redirectTo;
            }

            $ssoURL = $settingsInfo['idp']['singleSignOnService']['url'];
            $response = $this->getResponse();
            $helper->executePost($response, $ssoURL, $params);
        } else {
            $errorMsg = "You tried to start a SSO process but" .
                    " SAML2 module has wrong settings";
        }
    }
}
