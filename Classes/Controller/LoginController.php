<?php
declare(strict_types=1);
namespace WapplerSystems\Samlauth\Controller;



use OneLogin\Saml2\AuthnRequest;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Utils;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use WapplerSystems\Samlauth\Utility\Request;

/**
 */
class LoginController extends AbstractController
{


    /**
     * @param bool $do
     */
    public function buttonAction($do = false)
    {

        if ($do === true) {

            $idpSSOBinding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST';

            if ($idpSSOBinding === 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
                $this->postLogin();
            } else {
                $this->normalLogin();
            }
        }

    }

    private function normalLogin()
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

    private function postLogin()
    {

        $samlSettings = $this->configuration->getSAMLSettings();

        if (isset($samlSettings)) {
            try {
                $settings = new Settings($samlSettings);

                $authNRequest = new AuthnRequest($settings);
                $authNRequestXML = $authNRequest->getXML();

                if ($samlSettings['security']['authnRequestsSigned']) {
                    $key = $settings->getSPkey();
                    $cert = $settings->getSPcert();

                    $signatureAlgorithm = $samlSettings['security']['signatureAlgorithm'];
                    $digestAlgorithm = $samlSettings['security']['digestAlgorithm'];

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

                /** @var UriBuilder $uriBuilder */
                $uriBuilder = $this->objectManager->get(UriBuilder::class);

                $successUrl = $uriBuilder->reset()->setCreateAbsoluteUri(true)->setArguments(['id' => 1])->buildFrontendUri();
                if (!empty($successUrl)) {
                    $params['RelayState'] = $successUrl;
                }

                $ssoURL = $samlSettings['idp']['singleSignOnService']['url'];

                Request::executePost($this->response, $ssoURL, $params);

            } catch (Error $e) {
            } catch (\Exception $e) {
            }

        } else {
            $errorMsg = "You tried to start a SSO process but" .
                " SAML2 module has wrong settings";
        }
    }


}
