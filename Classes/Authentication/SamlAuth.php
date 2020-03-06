<?php
declare(strict_types=1);
namespace WapplerSystems\Samlauth\Authentication;


use OneLogin\Saml2\Auth;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Sv\AuthenticationService;
use WapplerSystems\Samlauth\Configuration;
use WapplerSystems\Samlauth\Repository\IdentityProviderRepository;
use WapplerSystems\Samlauth\Service\UserCreator;

class SamlAuth extends AuthenticationService
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var array|false
     */
    private $configuration;

    public function __construct()
    {
        $this->om = GeneralUtility::makeInstance(ObjectManager::class);

        $this->configuration = $this->om->get(IdentityProviderRepository::class)
            ->findByHostname(GeneralUtility::getIndpEnv('HTTP_HOST'));
    }


    public function getUser()
    {
        if ($this->login['status'] !== 'login' && empty(GeneralUtility::_POST('SAMLResponse'))) {
            return NULL;
        }

        if (!is_array($this->configuration)) {
            return false;
        }

        if (GeneralUtility::_POST('SAMLResponse') !== null) {

            /** @var Configuration $configuration */
            $configuration = $this->om->get(\WapplerSystems\Samlauth\Configuration::class);


            $auth = new Auth($configuration->getSAMLSettings());
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
            $userCreator = $this->om->get(UserCreator::class);

            $frontendUser = $userCreator->updateOrCreate($auth->getAttributes(), ['user_folder' => 14]);
            if ($frontendUser) {
                return $this->pObj->getRawUserByUid($frontendUser->getUid());
            }
        }

        return null;
    }

    public function authUser(array $user): int
    {
        return is_array($this->configuration) ? 200 : 0;
    }



}
