<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Block\Login;

use \Magento\Framework\View\Element\AbstractBlock;
use \Magento\Framework\View\Element\Template\Context;
use \Pitbulk\SAML2\Helper\Data;

class Elements extends AbstractBlock
{
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    public function _toHtml()
    {
        $html = '';
        $helper = $this->helper;

        $moduleEnabled = $helper->checkEnabledModule();
        if ($moduleEnabled) {
            $ssoLoginUrl = $helper->getBaseStoreUrl()."sso/saml2/login";
            $customParamBase = 'pitbulk_saml2_customer/customizations/';
            $headerText = $helper->getConfig($customParamBase.'login_header');
            $linkText = $helper->getConfig($customParamBase.'login_link');

            if (empty($headerText)) {
                $headerText = 'External customers';
            }
            if (empty($linkText)) {
                $linkText = 'Login via Identity Provider';
            }

            $html .= '
    <div class="block-title">
       <strong role="heading">'.$headerText.'</strong>
    </div>
    <div class="block-content">
       <a class="action login primary"
          href="'.$ssoLoginUrl.'">'.$linkText.'</a>
    </div>';
        }
        return $html;
    }
}
