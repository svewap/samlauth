<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

use Pitbulk\SAML2\Model\AuthFactory;

class Data extends AbstractHelper
{
    private $customerSession;
    private $storeManager;
    private $urlInterface;
    private $configSectionId = 'pitbulk_saml2_customer';
    private $authFactory;

    public function __construct(
        Context $context,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        AuthFactory $authFactory
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->urlInterface = $context->getUrlBuilder();
        $this->authFactory = $authFactory;

        parent::__construct($context);
    }

    public function getBaseStoreUrl()
    {
        return $this->storeManager->getStore()
                                   ->getBaseUrl(UrlInterface::URL_TYPE_LINK);
    }

    public function getWebsiteId()
    {
        return $this->storeManager
                    ->getStore()
                    ->getWebsiteId();
    }

    public function getCurrentUrl()
    {
        return $this->urlInterface->getCurrentUrl();
    }

    public function getUrl($path, $param = [])
    {
        return $this->storeManager
                    ->getStore()
                    ->getUrl($path, $param);
    }

    public function getConfig($path, $scope = null, $store = null)
    {
        if ($scope === null) {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        if ($store === null) {
            $store = $this->storeManager->getStore();
        }

        return $this->scopeConfig
                    ->getValue($path, $scope, $store);
    }

    public function getConfigIdP($path, $scope = null, $store = null)
    {
        $path = $this->configSectionId.'/idp/' . $path;
        return $this->getConfig($path, $scope, $store);
    }

    public function getConfigAdvanced($path, $scope = null, $store = null)
    {
        $path = $this->configSectionId.'/advanced/' . $path;

        return $this->getConfig($path, $scope, $store);
    }

    /**
     * Get if module is enabled
     *
     * @return bool
     */
    public function checkEnabledModule()
    {
        $enabled = $this->getConfig($this->configSectionId.'/status/enabled');
        return (bool) $enabled;
    }

    /**
     * Get saml auth
     *
     * @return Auth
     */
    public function getAuth()
    {
        $settingsInfo = $this->getSettings();
        $auth = $this->authFactory->create(["settings" => $settingsInfo]);
        return $auth;
    }

    /**
     * Get Settings
     *
     * @return Array
     */
    public function getSettings()
    {
        $samlStrict = $this->getConfigAdvanced('strict');
        $samlDebug = $this->getConfigAdvanced('debug');

        $idpEntityid = $this->getConfigIdP('entityid');
        $idpSSO = $this->getConfigIdP('sso');
        $idpSSOBinding = $this->getConfigIdP('sso_binding');
        $idpSLO = $this->getConfigIdP('slo');

        $spEntityid = $this->getConfigAdvanced('entityid');
        $spNameIDFormat = $this->getConfigAdvanced('nameidformat');

        $nameIdEncrypted = $this->getConfigAdvanced('nameid_encrypted');
        $authnReqsSigned = $this->getConfigAdvanced('authn_request_signed');
        $logoutReqSigned = $this->getConfigAdvanced('logout_request_signed');
        $logoutResSigned = $this->getConfigAdvanced('logout_response_signed');
        $wMesSigned = $this->getConfigAdvanced('want_message_signed');
        $wAssertSigned = $this->getConfigAdvanced('advanced/want_assertion_signed');
        $wAssertEncrypted = $this->getConfigAdvanced('want_assertion_encrypted');

        $signatureAlgorithm = $this->getConfigAdvanced('signaturealgorithm');

        $digestAlgorithm = $this->getConfigAdvanced('digestalgorithm');

        $lowerCaseUrlEncoding = $this->getConfigAdvanced('lowercaseurlencoding');

        $reqAuthnContext = $this->getConfigAdvanced('requestedauthncontext');

        if (isset($reqAuthnContext)) {
            if (!is_array($reqAuthnContext)) {
                $reqAuthnContext = explode(',', $reqAuthnContext);
            }
        } else {
            $reqAuthnContext = false;
        }

        $signMetadata = $this->getConfigAdvanced('metadata_signed');

        $defaultSSOBinding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
        $defaultSig = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
        $defaultDigestAlg = 'http://www.w3.org/2000/09/xmldsig#sha1';
        $defaultNameID = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';

        $settings = [
            'strict' => isset($samlStrict)? $samlStrict : false,
            'debug' => isset($samlDebug)? $samlDebug : false,

            'sp' => [
                'entityId' => $spEntityid ? $spEntityid : $this->getBaseStoreUrl()."sso/saml2/metadata",
                'assertionConsumerService' => [
                    'url' => $this->getBaseStoreUrl().'sso/saml2/acs',
                ],
                'singleLogoutService' => [
                    'url' => $this->getBaseStoreUrl().'sso/saml2/sls',
                ],
                'NameIDFormat' => $spNameIDFormat ? $spNameIDFormat : $defaultNameID,
            ],
            'idp' => [
                'entityId' => $idpEntityid,
                'singleSignOnService' => [
                    'url' => $idpSSO,
                    'binding'=> isset($idpSSOBinding)? $idpSSOBinding : $defaultSSOBinding,
                ],
            ],
            'security' => [
                'signMetadata' => isset($signMetadata)? (bool)$signMetadata : false,
                'nameIdEncrypted' => isset($nameIdEncrypted)? $nameIdEncrypted : false,
                'authnRequestsSigned' => isset($authnReqsSigned)? $authnReqsSigned : false,
                'logoutRequestSigned' => isset($logoutReqSigned)? $logoutReqSigned : false,
                'logoutResponseSigned' => isset($logoutResSigned)? $logoutResSigned : false,
                'wantMessagesSigned' => isset($wMesSigned)? $wMesSigned : false,
                'wantAssertionsSigned' => isset($wAssertSigned)? $wAssertSigned : false,
                'wantAssertionsEncrypted' => isset($wAssertEncrypted)? $wAssertEncrypted : false,
                'signatureAlgorithm' => isset($signatureAlgorithm)? $signatureAlgorithm : $defaultSig,
                'digestAlgorithm' => isset($digestAlgorithm)? $digestAlgorithm : $defaultDigestAlg,
                'lowercaseUrlencoding' => isset($lowerCaseUrlEncoding)? $lowerCaseUrlEncoding : false,
                'requestedAuthnContext' => !empty($reqAuthnContext)? $reqAuthnContext : false,
                'relaxDestinationValidation' => true,
                'wantNameId' => false
            ]
        ];

        $spCert = $this->getConfigAdvanced('x509cert');
        $spPrivatekey = $this->getConfigAdvanced('privatekey');

        $idpX509cert = $this->getConfigIdP('x509cert');
        $idpX509cert2 = $this->getConfigIdP('x509cert2');
        $idpX509cert3 = $this->getConfigIdP('x509cert3');

        $settings['idp']['x509certMulti'] = [
              'signing' => [
                  0 => $idpX509cert,
              ],
              'encryption' => [
                  0 => $idpX509cert,
              ]
        ];
        if (!empty($idpX509cert2)) {
            $settings['idp']['x509certMulti']['signing'][] = $idpX509cert2;
        }
        if (!empty($idpX509cert3)) {
            $settings['idp']['x509certMulti']['signing'][] = $idpX509cert3;
        }

        if (!empty($spCert)) {
            $settings['sp']['x509cert'] = $spCert;
        }

        if (!empty($spPrivatekey)) {
            $settings['sp']['privateKey'] = $spPrivatekey;
        }

        if (!empty($idpSLO)) {
            $settings['idp']['singleLogoutService']['url'] = $idpSLO;
        }

        return $settings;
    }

    public function executePost($response, $url, $params)
    {
        $html = '<html>';

        $html .= '<body onload="document.getElementById(\'send\').click();">';
        $html .= '<form method="POST" action="'.$url.'">';
        foreach ($params as $key => $value) {
            $html .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
        }

        $html .= '<input type="submit" id="send" value="Send">';
        $html .= '</form>';
        $html .= '</body>';
        $html .= '</html>';

        $response->setContent($html);
    }

    public function executePostCurl($url, $params)
    {
        $ch = curl_init();
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params)
        ];
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
    }
}
