<?php

namespace Pitbulk\SAML2\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Disable customer email depending on settings value.
 */
class EmailNotificationPlugin
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
     * Send email with new account related information
     *
     * @param CustomerInterface $customer
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @param string $sendemailStoreId
     * @return void
     * @throws LocalizedException
     */
    public function aroundNewAccount(
        \Magento\Customer\Model\EmailNotification $emailNotification,
        callable $proceed,
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $type = self::NEW_ACCOUNT_EMAIL_REGISTERED,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ) {
        if (!$this->scopeConfig->getValue(
            'pitbulk_saml2_customer/options/disablenewcustomermail',
            'store',
            $storeId
        )
        ) {
            return $proceed($customer, $type, $backUrl, $storeId, $sendemailStoreId);
        } else {
            return $emailNotification;
        }
    }
}
