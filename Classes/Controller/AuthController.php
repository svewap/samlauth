<?php
declare(strict_types=1);

namespace WapplerSystems\Samlauth\Controller;


use OneLogin\Saml2\AuthnRequest;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\LogoutRequest;
use OneLogin\Saml2\Response;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Utils;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use WapplerSystems\Samlauth\Exception\MissingConfigurationException;
use WapplerSystems\Samlauth\Exception\RuntimeException;
use WapplerSystems\Samlauth\Utility\Request;

/**
 */
class AuthController extends AbstractController
{


    /**
     * @return string
     * @throws MissingConfigurationException
     */
    public function metadataAction(): string
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
     * @param string|null $subAction
     * @param string|null $redirectTo
     * @throws StopActionException
     * @throws MissingConfigurationException
     */
    public function authAction(string $subAction = null, string $redirectTo = null): ResponseInterface
    {

        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'text/html; charset=utf-8');


        $flag = $GLOBALS['T3_VAR']['samlAuth'] ?? 0;
        //DebugUtility::debug($GLOBALS['T3_VAR']);
        //DebugUtility::debug($this->getTypoScriptFrontendController()->fe_user);

        if ($flag === 1) {
            // successful login

            $redirectAfterLoginUrl = $GLOBALS['T3_VAR']['samlAuthRedirectAfterLogin'] ?? null;

            if ($redirectAfterLoginUrl !== null && $redirectAfterLoginUrl !== '') {
                //$this->redirectToUri($redirectAfterLoginUrl);
            }

            if ((int)$this->settings['redirectAfterLogin'] > 0) {
                //$this->redirectToUri($this->uriBuilder->reset()->setTargetPageUid((int)$this->settings['redirectAfterLogin'])->buildFrontendUri());
            }

            $this->addFlashMessage(LocalizationUtility::translate('LLL:EXT:samlauth/Resources/Private/Language/locallang.xlf:flashMessage.successfulLogin'), '',
                AbstractMessage::INFO);

        }
        if ($flag === 2) {
            // login failure
            $this->addFlashMessage(LocalizationUtility::translate('LLL:EXT:samlauth/Resources/Private/Language/locallang.xlf:flashMessage.loginFailure'), '',
                AbstractMessage::ERROR);
        }

        if ($subAction === 'login') {
            $this->postLogin($response, $redirectTo);
        } else if ($subAction === 'logout') {
            $this->postLogout($response);
        }

        $isAuthorized = false;

        if ($this->getTypoScriptFrontendController()->fe_user->user !== null) {
            $isAuthorized = true;
        }

        $this->view->assignMultiple([
            'authorized' => $isAuthorized,
            'redirectTo' => $_GET['redirect_url'] ?? $this->uriBuilder->reset()->setCreateAbsoluteUri(true)->setTargetPageUid($this->getTypoScriptFrontendController()->id)->buildFrontendUri(),
        ]);

        $response->getBody()->write($this->view->render());

        return $response;
    }


    /**
     * @param null $redirectTo
     * @throws MissingConfigurationException
     */
    private function postLogin(ResponseInterface $response, $redirectTo = null): void
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

                Request::executePost($response, $ssoURL, $params);

            } catch (Error $e) {
            } catch (\Exception $e) {
            }

        } else {
            $errorMsg = "You tried to start a SSO process but SAML2 module has wrong settings";
        }
    }


    private function postLogout(ResponseInterface $response): void
    {

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
                    'RelayState' => $samlSettings['sp']['singleLogoutService']['url'] ?? $this->uriBuilder->getRequest()->getRequestUri(),
                ];

                $ssoURL = $samlSettings['idp']['singleLogoutService']['url'];



                Request::executePost($response, $ssoURL, $params);

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
     * @throws MissingConfigurationException
     * @throws StopActionException
     */
    public function singleLogoutServiceAction()
    {

        if (GeneralUtility::_POST('SAMLResponse') == null) {
            throw new RuntimeException('No SAML Response found in POST data', 1621608323);
        }

        $samlSettings = $this->configurationProvider->getSAMLSettings();
        $settings = new Settings($samlSettings);
        $response = new Response($settings, GeneralUtility::_POST('SAMLResponse'));

        $valid = $response->isValid();

        // TODO: Check validation

        //DebugUtility::debug($valid);


        $feController = $this->getTypoScriptFrontendController();

        // Workaround because of cookie policy
        if ((int)$this->settings['redirectAfterLogout'] > 0) {
            $this->redirectToUri($this->uriBuilder->reset()->setTargetPageUid((int)$this->settings['redirectAfterLogout'])->setArguments(['logintype' => 'logout'])->buildFrontendUri());
        }


    }


    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }


}
