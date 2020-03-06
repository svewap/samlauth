<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Model\Config\Source;

use Pitbulk\SAML2\Model\Config\Source\AbstractArrayInterface;

class SearchCriteria extends AbstractArrayInterface
{
    public $values = [
        'LIKE',
        'EQ'
    ];
}
