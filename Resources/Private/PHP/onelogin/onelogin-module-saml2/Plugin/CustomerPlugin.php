<?php

namespace Pitbulk\SAML2\Plugin;

/**
 * Disable customer email depending on settings value.
 */
class CustomerPlugin
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * CustomerPlugin constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param callable $proceed
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     *
     * @return mixed
     */
    public function aroundSendNewAccountEmail(
        \Magento\Customer\Model\Customer $customer,
        callable $proceed,
        $type = 'registered',
        $backUrl = '',
        $storeId = '0'
    ) {
        if (!$this->scopeConfig->getValue(
            'pitbulk_saml2_customer/options/disablenewcustomermail',
            'store',
            $storeId
        )
        ) {
            return $proceed($type, $backUrl, $storeId);
        } else {
            return $customer;
        }
    }
}
