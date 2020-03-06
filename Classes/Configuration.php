<?php
namespace WapplerSystems\Samlauth;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use WapplerSystems\Samlauth\Repository\IdentityProviderRepository;
use WapplerSystems\Samlauth\Utility\UriBuilder;

class Configuration implements \TYPO3\CMS\Core\SingletonInterface
{


    /**
     * @return array|null
     */
    public function getIdentityConfiguration() {

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var IdentityProviderRepository $repository */
        $repository = $objectManager->get(IdentityProviderRepository::class);
        $record = $repository->findByHostname(GeneralUtility::getIndpEnv('HTTP_HOST'));

        if (false === is_array($record)) {
            return null;
        }

        return $record;
    }


    /**
     * Get Settings
     *
     * @return array
     */
    public function getSAMLSettings()
    {

        $identityConfig = $this->getIdentityConfiguration();

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $objectManager->get(UriBuilder::class);
        $uriBuilder->initializeObject();

        // sp identity id
        $spIdentityUrl = $uriBuilder->reset()->setCreateAbsoluteUri(true)->setArguments(['id' => 35, 'type' => 701001])->buildFrontendUri();
        //$spAcsUrl = $uriBuilder->reset()->setCreateAbsoluteUri(true)->setArguments(['id' => 35])->buildFrontendUri();
        $spAcsUrl = 'https://linear8.ddev.site/de/login/';
        //$spSlsUrl = $uriBuilder->reset()->setCreateAbsoluteUri(true)->setArguments(['id' => 35, 'type' => 701002])->buildFrontendUri();
        $spSlsUrl = 'https://linear8.ddev.site/de/login/?type=701002';


        $samlStrict = true;
        $samlDebug = true;

        $idpEntityid = $identityConfig['idp_entity_id'];
        $idpSSO = $identityConfig['url'];
        $idpSSOBinding = null;
        $idpSLO = $identityConfig['url'];

        $spEntityid = $identityConfig['name'];
        $spNameIDFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';

        $nameIdEncrypted = false;
        $authnReqsSigned = true;
        $logoutReqSigned = true;
        $logoutResSigned = true;
        $wMesSigned = false;
        $wAssertSigned = false;
        $wAssertEncrypted = false;

        $signatureAlgorithm = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';

        $digestAlgorithm = 'http://www.w3.org/2000/09/xmldsig#sha1';

        $lowerCaseUrlEncoding = false;

        $reqAuthnContext = '';

        if (isset($reqAuthnContext)) {
            if (!is_array($reqAuthnContext)) {
                $reqAuthnContext = explode(',', $reqAuthnContext);
            }
        } else {
            $reqAuthnContext = false;
        }

        $signMetadata = false;

        $defaultSSOBinding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
        $defaultSig = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
        $defaultDigestAlg = 'http://www.w3.org/2000/09/xmldsig#sha1';
        $defaultNameID = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';

        $settings = [
            'strict' => true,
            'debug' => true,

            'sp' => [
                'entityId' => $spEntityid,
                'assertionConsumerService' => [
                    'url' => $spAcsUrl,
                ],
                'singleLogoutService' => [
                    'url' => $spSlsUrl,
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

        $spCert = $identityConfig['certificate'];
        $spPrivatekey = $identityConfig['cert_key'];

        $idpX509cert = $identityConfig['idp_certificate'];
        $idpX509cert2 = '';
        $idpX509cert3 = '';

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




}