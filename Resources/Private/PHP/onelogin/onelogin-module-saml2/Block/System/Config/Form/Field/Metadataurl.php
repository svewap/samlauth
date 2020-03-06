<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Block\System\Config\Form\Field;

use \Magento\Config\Block\System\Config\Form\Field;
use \Magento\Backend\Block\Template\Context;
use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Framework\UrlInterface;
use \Magento\Store\Model\ScopeInterface;

class Metadataurl extends Field
{
    public $request;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(Context $context, Http $request)
    {
        $this->request = $request;
        parent::__construct($context, []);
    }

    public function _getElementHtml(AbstractElement $element)
    {
        $websiteId = $this->request->getParam('website', null);
        if (isset($websiteId)) {
            $websiteId = (int) $websiteId;
            $website = $this->_storeManager->getWebsite($websiteId);
            $metadataUrl = $website->getDefaultStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK);
            $license = $this->_scopeConfig->getValue(
                'pitbulk_saml2_customer/status/license',
                ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            );
        } else {
            $storeId = $this->request->getParam('store', null);
            if (isset($storeId)) {
                $storeId = (int) $storeId;
            }
            $store = $this->_storeManager->getStore($storeId);
            $metadataUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);
            $license = $this->_scopeConfig->getValue(
                'pitbulk_saml2_customer/status/license',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        $metadataUrl .= "sso/saml2/metadata";

        $licenseHtml = "";

        if (!empty($license)) {
            $licenseHtml = '&license='.urlencode($license);
        }

        $html = '<label id="' . $element->getHtmlId() .
                     '" name="' . $element->getName() .
                     '"></label><a target="_blank" 
                    href="'.$metadataUrl.'">'.$metadataUrl.'</a>';

        $url = 'https://logs-01.loggly.com/inputs/'.
               '30c06b29-45ba-411f-a89c-60a9eb5f8e22.gif'.
               '?source=pixel'.$licenseHtml.'&extension=magento2';

        $html .= '<img style="width: 0px;height: 0px;" src="'.$url.'" />';

        return $html;
    }

    /**
     * Render button
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
