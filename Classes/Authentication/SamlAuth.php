<?php
declare(strict_types=1);
namespace WapplerSystems\Samlauth\Authentication;


use OneLogin\Saml2\Auth;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Sv\AuthenticationService;
use WapplerSystems\Samlauth\ConfigurationProvider;
use WapplerSystems\Samlauth\Exception\MissingConfigurationException;
use WapplerSystems\Samlauth\Service\UserCreator;

class SamlAuth extends AuthenticationService
{

    /**
     * @var null
     */
    var $userUid = null;

    public function getUser()
    {

        $GLOBALS['T3_VAR']['samlAuth'] = 0;

        if ($this->login['status'] !== 'login' && empty(GeneralUtility::_POST('SAMLResponse'))) {
            return NULL;
        }

        if (GeneralUtility::_POST('SAMLResponse') !== null) {

            /** @var ObjectManager $om */
            $om = GeneralUtility::makeInstance(ObjectManager::class);

            /** @var $logger Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            /** @var ConfigurationProvider $configurationProvider */
            $configurationProvider = $om->get(ConfigurationProvider::class);


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
            $userCreator = $om->get(UserCreator::class);

            //DebugUtility::debug($auth->getAttributes());
            //DebugUtility::debug($auth->getAttributesWithFriendlyName());

            $frontendUser = $userCreator->updateOrCreate($auth->getAttributes(), $configuration);

            $GLOBALS['T3_VAR']['samlAuth'] = 1;
            $GLOBALS['T3_VAR']['samlAuthRedirectAfterLogin'] = $_POST['RelayState'] ?? null;


            if ($frontendUser) {
                $this->userUid = $frontendUser->getUid();
                return $this->pObj->getRawUserByUid($frontendUser->getUid());
            }
        }

        return null;
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
                $customAttrIdentifier = $helper->getConfig('pitbulk_saml2_customer/custom_field_mapping/custom_attribute_mapping');
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

    public function authUser(array $user): int
    {
        return ($this->userUid !== null) ? 200 : 0;
    }

}
