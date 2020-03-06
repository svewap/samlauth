/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require(
    [
        'jquery',
        'mage/translate',
        'jquery/validate'
    ],
    function ($) {
        $.validator.addMethod(
            'validate-saml',
            function (v, e) {
                if (v == "1") {
                    if ($('#pitbulk_saml2_customer_idp_entityid').val() == '' ||
                      $('#pitbulk_saml2_customer_idp_sso').val() == '' ||
                      $('#pitbulk_saml2_customer_idp_x509cert').val() == '') {
                        e.value = "0";
                        return false;
                    }
                }
                return true;
            },
            $.mage.__("Can't enable it. At Identity Provider Settings section: 'IdP Entity Id', 'Single Sign On Service Url' and 'X.509 Certificate' are mandatory")
        );
    }
);