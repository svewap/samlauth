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
use Pitbulk\SAML2\Helper\Data;

class SLS extends AbstractCustomController
{
    public function execute()
    {
        $customerSession = $this->_getCustomerSession();

        // Prevent if not logged
        if (!$customerSession->isLoggedIn()) {
            $this->_redirect('/');
            return;
        }

        $helper = $this->_getHelper();

        $moduleEnabled = $helper->checkEnabledModule();
        if ($moduleEnabled) {
            $auth = $this->_getSAMLAuth();

            $auth->processSLO();
            $errors = $auth->getErrors();
            if (empty($errors)) {
                // local logout
                $customerSession->unsetData('saml_login');
                $customerSession->logout();
                return $this->_redirect('customer/account/logoutSuccess');
            } else {
                $errorMsg = 'Error at the SLS Endpoint.<br>' .
                            implode(', ', $errors);
                if ($helper->getConfig('pitbulk_saml2_customer/advanced/debug')) {
                    $reason = $auth->getLastErrorReason();
                    if (isset($reason) && !empty($reason)) {
                        $errorMsg .= '<br><br>Reason: '.$reason;
                    }
                }
                $this->_processError($errorMsg);
            }
        } else {
            $this->_processError('SAML module has disabled status');
        }
    }
}
