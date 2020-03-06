<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Controller\Saml2;

use Pitbulk\SAML2\Controller\AbstractCustomController;

class Logout extends AbstractCustomController
{
    public function execute()
    {
        $helper = $this->_getHelper();
        $customerSession = $this->_getCustomerSession();
        $errorMsg = null;

        $moduleEnabled = $helper->checkEnabledModule();
        if ($moduleEnabled) {
            $sloEnabled = $helper->getConfig('pitbulk_saml2_customer/options/slo');
            if ($sloEnabled) {
                if ($customerSession->isLoggedIn()
                  && $customerSession->getData('saml_login')) {
                    $auth = $this->_getSAMLAuth();

                    $redirectTo =  $helper->getUrl('customer/account/logoutSuccess');
                    $auth->logout(
                        $redirectTo,
                        [],
                        $customerSession->getData('saml_nameid'),
                        $customerSession->getData('saml_sessionindex')
                    );
                } else {
                    $errorMsg = "You tried to start a SLO process but you" .
                                " are not logged via SAML";
                }
            } else {
                $errorMsg = "You tried to start a SLO process but this" .
                            " functionality is disabled";
            }
        } else {
            $errorMsg = "You tried to start a SLO process but SAML2 module" .
                        " has disabled status";
        }

        if (isset($errorMsg)) {
            $this->_processError($errorMsg);
        }
    }
}
