Single Sign On. SAML extension for Magento 2. Implemented by Sixto Martin
=========================================================================

Description
-----------

Magento2 extension that add SAML Single Sign On support to the customer login page. 


If you are working with a partner that has implemented a SAML identity provider, you can use this extension to interoperate with it, thereby enabling SSO for customers. It works with any IDP providers, including OneLogin, Okta, Ping Identity, ADFS, Salesforce, SharePoint...

The module was implemented by [Sixto Martin](http://pitbulk.github.io) , author of 15+ SAML plugins and several SAML toolkits.

The module was implemented for Magento 2, If you are interested in a SAML module compatible with Magento 1.X search on the Magento marketplace for a Magento1 version.

Customers are happy with the SAML extension I made and with the support received. Companies like Cisco, Toyota or PWC trusted in the SAML extensions.


How does it work?
-----------------

The normal usage
................

Extension adds a link "Login via Identity provider" at the customer login form. 
Following this links initiates series of redirects that are described by [SAML 2.0 standart](http://en.wikipedia.org/wiki/SAML_2.0)

User authenticates against the SAML Identity Provider and then information about user, group and address is sent to Magento. Magento authenticate user and let him in.

Other usages
............

Extension supports IdP-Initiated so a SAML Response can be directly processed by the Magento instance.



Features
--------

* Allow to Login via any Identity Provider.
* Easily switch On/Off the SAML Module.
* Supports Single Sign On (IdP and SP initiated)
* Supports Single Log Out (IdP and SP initiated)
* Supports Just-In-Time Provisioning (user data + group + address, custom attributes)
* Possibly set the mapping between IdP fields and Magento fields.
* Customizable workflow.
* Supports Magento Multi-stores.
* Documented settings



Installation
------------

Install the package using Magento Connect utility or review the Installation.md file.

Settings
--------

The Settings of the extension are available at Stores > Configuration. At the Services tab, the "SAML SSO for customers" link.

There you will be able to fill several sections:

 * Status. To enable or disable the extension. 
 * Identity Provider. Set parameters related to the IdP that will be connected with our Magento.
 * Options. The behavior of the extension. 
 * Attribute Mapping. Set the mapping between IdP fields and Magento user fields.
 * Group Mapping. Set the mapping between IdP groups and Magento groups.
 * Address Mapping. Set the mapping between IdP fields and Magento address fields.
 * Custom Mapping. Set the mapping between IdP fields and Magento custom fields. You will also be able to identify magento accounts by a custom field instead the mail.
 * Custom messages. To handle what messages are showed in the login form.
 * Advanced settings. Handle some other parameters related to customizations and security issues.


The metadata of the Magento Service Provider will be available at http://<magento_base_url>/sso/saml/metadata

At the Status section you are asked for a license key. Use the Order ID of your magento marketplace’s purchase.


Warranty
--------

Support by mail <sixto.martin.garcia@gmail.com> guaranteed. Get a reply in less than 48h (business day).


License warning
---------------

When you purchase the extension, you are able to use it at one M2 instance.

In case of M2 running multi-sites, the license cover 3 stores using SAML SSO. If you require more stores, contact sixto.martin.garcia@gmail.com to discuss the terms.

Test and developer environments can use the extension without require an additional license
