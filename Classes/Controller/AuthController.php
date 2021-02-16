<?php
declare(strict_types=1);

namespace WapplerSystems\Samlauth\Controller;


use OneLogin\Saml2\AuthnRequest;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\LogoutRequest;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Utils;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use WapplerSystems\Samlauth\Utility\Request;

/**
 */
class AuthController extends AbstractController
{


    /**
     * @return string
     */
    public function metadataAction()
    {

        $samlSettings = $this->configurationProvider->getSAMLSettings();

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


    /**
     *
     * Login - Button
     * or
     * Display flash message
     *
     * @param string $subAction
     * @param string $redirectTo
     * @throws StopActionException|\TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function authAction($subAction = null, $redirectTo = null)
    {
        $flag = $GLOBALS['T3_VAR']['samlAuth'] ?? 0;
        if ($flag === 1) {
            // successful login

            $this->addFlashMessage(LocalizationUtility::translate('LLL:EXT:samlauth/Resources/Private/Language/locallang.xlf:flashMessage.successfulLogin'), '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);

            $redirectAfterLoginUrl = $GLOBALS['T3_VAR']['samlAuthRedirectAfterLogin'] ?? null;
            if ($redirectAfterLoginUrl !== null || $redirectAfterLoginUrl !== '') {
                $this->redirectToUri($redirectAfterLoginUrl);
            }

            if ((int)$this->settings['redirectAfterLogin'] > 0) {
                $this->redirectToUri($this->uriBuilder->reset()->setTargetPageUid((int)$this->settings['redirectAfterLogin'])->buildFrontendUri());
            }

        }
        if ($flag === 2) {
            // login failure
            $this->addFlashMessage(LocalizationUtility::translate('LLL:EXT:samlauth/Resources/Private/Language/locallang.xlf:flashMessage.loginFailure'), '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        }

        if ($subAction === 'login') {
            $this->postLogin($redirectTo);
        } else if ($subAction === 'logout') {
            $this->postLogout();
        }

        $isAuthorized = false;
        if ($GLOBALS['TSFE']->loginUser !== false) {
            $isAuthorized = true;
        }

        $this->view->assignMultiple([
            'authorized' => $isAuthorized,
            'redirectTo' => $_GET['redirect_url'] ?? '',
        ]);

    }


    /**
     * @param null $redirectTo
     * @throws \WapplerSystems\Samlauth\Exception\MissingConfigurationException
     */
    private function postLogin($redirectTo = null)
    {

        $samlSettings = $this->configurationProvider->getSAMLSettings();

        if (isset($samlSettings)) {
            try {
                $settings = new Settings($samlSettings);

                $authnRequest = new AuthnRequest($settings);
                $authnRequestXML = $authnRequest->getXML();

                if ($samlSettings['security']['authnRequestsSigned']) {
                    $key = $settings->getSPkey();
                    $cert = $settings->getSPcert();

                    $signatureAlgorithm = $samlSettings['security']['signatureAlgorithm'];
                    $digestAlgorithm = $samlSettings['security']['digestAlgorithm'];

                    $signedAuthNRequestXML = Utils::addSign(
                        $authnRequestXML,
                        $key,
                        $cert,
                        $signatureAlgorithm,
                        $digestAlgorithm
                    );

                    $encodedAuthNRequest = base64_encode($signedAuthNRequestXML);
                } else {
                    $encodedAuthNRequest = base64_encode($authnRequestXML);
                }

                $params = [
                    'SAMLRequest' => $encodedAuthNRequest,
                    'RelayState' => $redirectTo ?? $samlSettings['sp']['assertionConsumerService']['url'],
                ];

                $ssoURL = $samlSettings['idp']['singleSignOnService']['url'];

                Request::executePost($this->response, $ssoURL, $params);

            } catch (Error $e) {
            } catch (\Exception $e) {
            }

        } else {
            $errorMsg = "You tried to start a SSO process but SAML2 module has wrong settings";
        }
    }


    private function postLogout() {

        $samlSettings = $this->configurationProvider->getSAMLSettings();


        if (isset($samlSettings)) {
            try {
                $settings = new Settings($samlSettings);

                $logoutRequest = new LogoutRequest($settings);
                $logoutRequestXML = $logoutRequest->getXML();

                if ($samlSettings['security']['authnRequestsSigned']) {
                    $key = $settings->getSPkey();
                    $cert = $settings->getSPcert();

                    $signatureAlgorithm = $samlSettings['security']['signatureAlgorithm'];
                    $digestAlgorithm = $samlSettings['security']['digestAlgorithm'];


                    $signedAuthNRequestXML = Utils::addSign(
                        $logoutRequestXML,
                        $key,
                        $cert,
                        $signatureAlgorithm,
                        $digestAlgorithm
                    );

                    $encodedAuthNRequest = base64_encode($signedAuthNRequestXML);
                } else {
                    $encodedAuthNRequest = base64_encode($logoutRequestXML);
                }

                $params = [
                    'SAMLRequest' => $encodedAuthNRequest,
                    'RelayState' => $this->uriBuilder->getRequest()->getRequestUri(),
                ];

                $ssoURL = $samlSettings['idp']['singleLogoutService']['url'];

                Request::executePost($this->response, $ssoURL, $params);

            } catch (Error $e) {
            } catch (\Exception $e) {
            }

        } else {
            $errorMsg = "You tried to start a SSO process but SAML2 module has wrong settings";
        }

    }


    /**
     * Destroy user session
     *
     * @throws \WapplerSystems\Samlauth\Exception\MissingConfigurationException
     */
    public function singleLogoutServiceAction()
    {




        $samlSettings = $this->configurationProvider->getSAMLSettings();




    }





}
