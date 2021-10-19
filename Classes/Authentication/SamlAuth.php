<?php
declare(strict_types=1);
namespace WapplerSystems\Samlauth\Authentication;


use OneLogin\Saml2\Auth;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\Samlauth\ConfigurationProvider;
use WapplerSystems\Samlauth\Exception\MissingConfigurationException;
use WapplerSystems\Samlauth\Model\FrontendUser;
use WapplerSystems\Samlauth\Service\UserCreator;

class SamlAuth extends AuthenticationService
{

    /**
     * @var null
     */
    var $userUid = null;

    /**
     * Find a user
     *
     * @return mixed User array or FALSE
     */
    public function getUser()
    {

        $GLOBALS['T3_VAR']['samlAuth'] = 0;

        if ($this->login['status'] !== LoginType::LOGIN && empty(GeneralUtility::_POST('SAMLResponse'))) {
            return NULL;
        }
        if ($this->login['status'] === LoginType::LOGOUT) {
            return NULL;
        }

        if (GeneralUtility::_POST('SAMLResponse') !== null) {

            /** @var $logger Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            /** @var ConfigurationProvider $configurationProvider */
            $configurationProvider = GeneralUtility::makeInstance(ConfigurationProvider::class);


            try {
                $configuration = $configurationProvider->getConfiguration();
                $auth = new Auth($configurationProvider->getSAMLSettings());
            } catch (MissingConfigurationException $e) {
                $logger->error($e->getMessage());
                return null;
            }
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
                return null;
            }
            if (!$auth->isAuthenticated()) {
                //$this->_processError("ACS Process failed");
                return null;
            }

            /** @var UserCreator $userCreator */
            $userCreator = GeneralUtility::makeInstance(UserCreator::class);

            //DebugUtility::debug($auth->getAttributes());
            //DebugUtility::debug($auth->getAttributesWithFriendlyName());


            $frontendUser = $userCreator->updateOrCreate($auth->getAttributes(), $configuration);

            $GLOBALS['T3_VAR']['samlAuth'] = 1;
            $GLOBALS['T3_VAR']['samlAuthRedirectAfterLogin'] = $_POST['RelayState'] ?? null;

            if ($frontendUser instanceof FrontendUser) {

                $this->userUid = $frontendUser->getUid();
                $user = $this->pObj->getRawUserByUid($frontendUser->getUid());

                $this->logger->info('Login from username "{username}"', [
                    'username' => $user['username'],
                    'REMOTE_ADDR' => $this->authInfo['REMOTE_ADDR'],
                ]);
                return $user;
            }
        }

        return null;
    }


    public function authUser(array $user): int
    {
        return ($this->userUid !== null) ? 200 : 0;
    }

}
