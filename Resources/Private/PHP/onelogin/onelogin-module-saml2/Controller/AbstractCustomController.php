<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Controller;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey;

use Psr\Log\LoggerInterface;

use Pitbulk\SAML2\Helper\Data;

abstract class AbstractCustomController extends Action
{
    private $helper;
    private $logger;
    private $customerSession;

    public function __construct(
        Context $context,
        Session $session,
        Data $helper,
        LoggerInterface $logger,
        FormKey $formKey
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->customerSession = $session;
        $this->formKey = $formKey;

        parent::__construct($context);

        $this->_whitelistEndpoint();
    }

    public function _whitelistEndpoint()
    {
        // CSRF Magento2.3 compatibility
        if (interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();
            if ($request instanceof HttpRequest && $request->isPost() && empty($request->getParam('form_key'))) {
                $request->setParam('form_key', $this->formKey->getFormKey());
            }
        }
    }

    public function _getSAMLAuth()
    {
        try {
            $helper = $this->_getHelper();
            $auth = $helper->getAuth();
            return $auth;
        } catch (\Exception $e) {
            $errorMsg = 'There is a problem with the SAML settings.';
            $this->_processError($errorMsg, $e->getMessage());
        }
    }

    public function _getSAMLSettings()
    {
        try {
            $helper = $this->_getHelper();
            $settings = $helper->getSettings();
            return $settings;
        } catch (\Exception $e) {
            $errorMsg = 'There is a problem with the SAML settings.';
            $this->_processError($errorMsg, $e->getMessage());
        }
    }

    public function _getCustomerSession()
    {
        return $this->customerSession;
    }

    public function _getHelper()
    {
        return $this->helper;
    }

    public function _processError($errorMsg, $extraInfo = null)
    {
        $this->messageManager->addError($errorMsg);
        $this->logger->error($errorMsg);
        if (isset($extraInfo)) {
            $this->logger->error($extraInfo);
        }
        return $this->_redirect($this->helper->getUrl('/'));
    }
}
