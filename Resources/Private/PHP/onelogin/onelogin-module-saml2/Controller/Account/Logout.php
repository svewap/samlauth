<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Controller\Account;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Pitbulk\SAML2\Helper\Data;

class Logout extends \Magento\Customer\Controller\Account\Logout
{
    /**
     * @var \Pitbulk\SAML2\Helper\Data
     */
    private $helper;

    /**
     * @param Context $context
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Data $helper
    ) {
        $this->helper = $helper;
        parent::__construct($context, $customerSession);
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        
        $skipNormalLogout = false;

        $moduleEnabled = $this->helper->checkEnabledModule();

        if ($moduleEnabled) {
            $sloParm = 'pitbulk_saml2_customer/options/slo';
            $sloEnabled = $this->helper->getConfig($sloParm);
            if ($this->session->isLoggedIn() &&
                $this->session->getData('saml_login')) {
                if ($sloEnabled) {
                    $skipNormalLogout = true;
                    // redirect to /sso/saml2/logout
                    $resultRedirect->setPath('sso/saml2/logout');
                }
            }
        }

        if (!$skipNormalLogout) {
            $resultRedirect = parent::execute();
        }

        return $resultRedirect;
    }
}
