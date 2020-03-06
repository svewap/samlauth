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

class Requestedauthncontext extends AbstractArrayInterface
{
    public $values = [
        'urn:oasis:names:tc:SAML:2.0:ac:classes:unspecified',
        'urn:oasis:names:tc:SAML:2.0:ac:classes:Password',
        'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport',
        'urn:oasis:names:tc:SAML:2.0:ac:classes:X509',
        'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard',
        'urn:oasis:names:tc:SAML:2.0:ac:classes:Kerberos',
        'urn:federation:authentication:windows'
    ];
}
