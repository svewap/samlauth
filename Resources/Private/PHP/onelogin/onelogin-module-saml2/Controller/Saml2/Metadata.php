<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Controller\Saml2;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;

use Pitbulk\SAML2\Controller\AbstractCustomController;
use Pitbulk\SAML2\Helper\Data;
use Pitbulk\SAML2\Model\SettingFactory;

use Psr\Log\LoggerInterface;

use OneLogin\Saml2\Error;

class Metadata extends AbstractCustomController
{
    private $settingFactory;

    public function __construct(
        Context $context,
        Session $session,
        Data $helper,
        LoggerInterface $logger,
        FormKey $formKey,
        SettingFactory $settingFactory
    ) {
            $this->settingFactory = $settingFactory;

            parent::__construct($context, $session, $helper, $logger, $formKey);
    }

    public function execute()
    {
        $helper = $this->_getHelper();
        $response = $this->getResponse();
        try {
            $settingsInfo = $helper->getSettings();
            $settings = $this->settingFactory->create(["settings" => $settingsInfo, "spValidationOnly" => true]);
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata($metadata);
            if (empty($errors)) {
                $response->setHeader('Content-Type', 'text/xml');
                $response->setHeader('SAML-Magento2', '1.5.0');
                $response->setContent($metadata);
            } else {
                throw new Error(
                    'Invalid SP metadata: '.implode(', ', $errors),
                    Error::METADATA_SP_INVALID
                );
            }
        } catch (\Exception $e) {
            $response->setHeader('Content-Type', 'text/html');
            $response->setHeader('SAML-Magento2', '1.4.0');
            $errorMsg = "Error on metadata view.".$e->getMessage();
            $response->setContent($errorMsg);
        }
    }
}
