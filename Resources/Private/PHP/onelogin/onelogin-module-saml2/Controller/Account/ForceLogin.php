<?php

namespace Pitbulk\SAML2\Controller\Account;

use Magento\Customer\Controller\Account\Login;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Pitbulk\SAML2\Helper\Data;

use Pitbulk\SAML2\Model\AuthnRequestFactory;
use Pitbulk\SAML2\Model\SettingFactory;
use OneLogin\Saml2\Utils;

class ForceLogin extends Login
{
    /**
     * @var \Pitbulk\SAML2\Helper\Data
     */
    private $helper;

    private $settingFactory;

    private $authnRequestFactory;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        Data $helper,
        SettingFactory $settingFactory,
        AuthnRequestFactory $authnRequestFactory
    ) {
        $this->helper = $helper;
        $this->settingFactory = $settingFactory;
        $this->authnRequestFactory = $authnRequestFactory;

        parent::__construct($context, $customerSession, $resultPageFactory);
    }

    public function execute()
    {
        if ($this->session->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        $moduleEnabled = $this->helper->checkEnabledModule('frontend');
        if ($moduleEnabled) {
            $forceSSOParm = 'pitbulk_saml2_customer/options/force_saml';
            $forceSSOEnabled = $this->helper->getConfig($forceSSOParm);

            if ($forceSSOEnabled) {
                $idpSSOBinding = $this->helper->getConfigIdP('sso_binding');
                if ($idpSSOBinding == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
                    $this->postLogin();
                } else {
                    $this->normalLogin();
                }
            }
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setHeader('Login-Required', 'true');
        return $resultPage;
    }

    private function normalLogin()
    {
        $auth = $this->helper->getAuth('frontend');
        if (isset($auth)) {
            $redirectTo = $this->session->getBeforeAuthUrl();
            if (!isset($redirectTo) || empty($redirectTo) ||
              strpos($redirectTo, '/customer/account/logoutSuccess') !== false &&
              strpos($redirectTo, '/sso/saml2') !== false) {
                $redirectTo =  $this->helper->getUrl('/');
            }
            $auth->login($redirectTo);
        }
    }

    private function postLogin()
    {
        $settingsInfo = $this->helper->getSettings('frontend');
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

            $redirectTo = $this->session->getBeforeAuthUrl();
            if (!isset($redirectTo) || empty($redirectTo) ||
                 strpos($redirectTo, '/customer/account/logoutSuccess') !== false) {
                $redirectTo =  $this->helper->getUrl('/');
            }

            if (!empty($redirectTo)) {
                $params['RelayState'] = $redirectTo;
            }

            $ssoURL = $settingsInfo['idp']['singleSignOnService']['url'];
            $response = $this->getResponse();
            $this->helper->executePost($response, $ssoURL, $params);
        }
    }
}
