<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */


namespace Pitbulk\SAML2\Model;

use Magento\Framework\ObjectManagerInterface;

use OneLogin\Saml2\Settings;

class SettingFactory
{
    private $objectManager = null;

    private $instanceName = null;

    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = '\\OneLogin\\Saml2\\Settings'
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    public function create(array $data = [])
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
