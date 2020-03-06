<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Controller\Saml2;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as CollectionRegionFactory;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CollectionCountryFactory;
use Magento\Directory\Model\RegionFactory;

use Psr\Log\LoggerInterface;

use Pitbulk\SAML2\Controller\AbstractCustomController;
use Pitbulk\SAML2\Helper\Data;

class ACS extends AbstractCustomController
{
    /**
     * @var AccountManagementInterface
     */
    private $customerAccountManagement;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var CollectionRegionFactory
     */
    private $regionCollectionFactory;

    /**
     * @var CollectionCountryFactory
     */
    private $countryCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var RegionFactory
     */
    private $regionFactory;

    public function __construct(
        Context $context,
        Session $session,
        Data $helper,
        LoggerInterface $logger,
        FormKey $formKey,
        AccountManagementInterface $customerAccountManagement,
        CustomerInterfaceFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        AddressInterfaceFactory $addressFactory,
        AddressRepositoryInterface $addressRepository,
        CollectionRegionFactory $regionCollectionFactory,
        CollectionCountryFactory $countryCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RegionFactory $regionFactory
    ) {
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->regionFactory = $regionFactory;

        parent::__construct($context, $session, $helper, $logger, $formKey);
    }

    public function execute()
    {
        $customerSession = $this->_getCustomerSession();

        $helper = $this->_getHelper();

        // Prevent if already logged
        if ($customerSession->isLoggedIn()) {
            $this->_redirect($helper->getUrl('/'));
            return;
        }

        $moduleEnabled = $helper->checkEnabledModule('frontend');
        if (!$moduleEnabled) {
            $this->_processError('SAML module has disabled status');
            return;
        }

        $auth = $this->_getSAMLAuth('frontend');
        $auth->processResponse();
        $errors = $auth->getErrors();
        if (!empty($errors)) {
            $errorMsg = "Error at the ACS Endpoint.<br>".implode(', ', $errors);
            $debug = $helper->getConfig('pitbulk_saml2_customer/advanced/debug');
            $reason = $auth->getLastErrorReason();
            if ($debug && isset($reason) && !empty($reason)) {
                $errorMsg .= '<br><br>Reason: '.$reason;
            }
            $this->_processError($errorMsg);
            return;
        } elseif (!$auth->isAuthenticated()) {
            $this->_processError("ACS Process failed");
            return;
        }

        $useCustomAttr = $helper->getConfig('pitbulk_saml2_customer/custom_field_mapping/use_custom_to_identity_user');

        $customerData = $this->processAttrs($auth, $useCustomAttr);

        if ($useCustomAttr) {
            if (empty($customerData['custom_attr']) || empty(reset($customerData['custom_attr']))) {
                $errorMsg = "SAML plugin can't obtain the custom value" .
                        " used to identify the user from the SAML Response. Review the data sent by your IdP";
                $this->_processError($errorMsg);
                return;
            }
        } elseif (empty($customerData['email'])) {
            $errorMsg = "SAML plugin can't obtain the email" .
                        " value from the SAML Response. Review the".
                        " data sent by your IdP and the Attribute" .
                        " Mapping setting options";
            $this->_processError($errorMsg);
            return;
        }

        try {
            if ($useCustomAttr) {
                $value = reset($customerData['custom_attr']);
                $key = key($customerData['custom_attr']);
                $searchCriteria = $helper->getConfig('pitbulk_saml2_customer/custom_field_mapping/search_criteria');
                if (empty($searchCriteria)) {
                    $searchCriteria = 'LIKE';
                }
                $this->searchCriteriaBuilder->addFilter($key, $value, $searchCriteria);
                $searchCriteria = $this->searchCriteriaBuilder->create();
                $list = $this->customerRepository->getList($searchCriteria);
                if ($list->getTotalCount() > 0) {
                    foreach ($list->getItems() as $item) {
                        $customer = $item;
                        break;
                    }
                }
            } else {
                $customer = $this->customerRepository->get($customerData['email']);
            }

            $this->_eventManager->dispatch('pitbulk_saml2_customer_check', ['customer' => $customer, 'customerData' => $customerData, 'samlAuth' => $auth]);

            if (!isset($customer)) {
                throw new NoSuchEntityException();
            }

            // Customer exists
            $customer = $this->updateCustomer($customer, $customerData, $useCustomAttr);
            $this->_eventManager->dispatch('pitbulk_saml2_customer_successfully_updated', ['customer' => $customer, 'customerData' => $customerData, 'samlAuth' => $auth]);

            $relayState = $this->getRequest()->getPost('RelayState');

            $urlToGo = '/';
        } catch (NoSuchEntityException $e) {
            // Customer doesn't exist
            $customer = $this->provisionCustomer($customerData);

            $this->_eventManager->dispatch('pitbulk_saml2_customer_successfully_created', ['customer' => $customer, 'customerData' => $customerData, 'samlAuth' => $auth]);

            $urlToGo = 'customer/address/';

            if (empty($customerData['address'])) {
                $urlToGo .= 'new';
            }
        }

        return $this->tryLogAndRedirect($customer, $customerSession, $auth, $urlToGo);
    }

    /**
     * Try log and redirect properly
     *
     */
    private function tryLogAndRedirect($customer, $customerSession, $auth, $urlToGo)
    {
        if (isset($customer)) {
            $this->registerCustomerSession($customerSession, $auth, $customer);

            $relayState = $this->getRequest()->getPost('RelayState');
            if (!empty($relayState) && $urlToGo == '/') {
                // Expects as $urlToGo an URL
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setUrl($relayState);
            } else {
                // Expects as $urlToGo a path
                $helper = $this->_getHelper();
                return $this->_redirect($helper->getUrl($urlToGo));
            }
        } else {
            $errorMsg = "SAML plugin failed trying to process the SSO. Review the Attribute Mapping section";
            $this->_processError($errorMsg);
            return;
        }
    }

    /**
     * Provision customer
     *
     */
    private function provisionCustomer($customerData)
    {
        $helper = $this->_getHelper();
        $autocreate = $helper->getConfig('pitbulk_saml2_customer/options/autocreate');
        if ($autocreate) {
            try {
                $customerEntity = $this->customerFactory->create();
                if (!empty($customerData['username'])) {
                    $customerEntity->setCustomAttribute('username', $customerData['username']);
                }
                // Custom Attr
                if (isset($customerData['custom_attr'])) {
                    foreach ($customerData['custom_attr'] as $key => $value) {
                        $customerEntity->setCustomAttribute($key, $value);
                    }
                }
                $customerEntity->setEmail($customerData['email']);
                $customerEntity->setFirstname($customerData['firstname']);
                $customerEntity->setLastname($customerData['lastname']);
                if (empty($customerData['groupid'])) {
                    $defaultGroup = $helper->getConfig('pitbulk_saml2_customer/options/defaultgroup');
                    if (!empty($defaultGroup)) {
                        $customerData['groupid'] = $defaultGroup;
                    } else {
                        $customerData['groupid'] = $helper->getConfig('customer/create_account/default_group');
                    }
                }
                $customerEntity->setGroupId($customerData['groupid']);
                $customer = $this->customerAccountManagement
                                 ->createAccount($customerEntity);

                if (!empty($customerData['address'])) {
                    $this->provisionAddress($customerData, $customer->getId());
                }

                $this->_eventManager->dispatch(
                    'customer_register_success',
                    ['account_controller' => $this, 'customer' => $customer]
                );
                $successMsg = __('Customer registration successful.');
                $this->messageManager->addSuccess($successMsg);
                return $customer;
            } catch (Exception $e) {
                $errorMsg = 'The auto-provisioning process failed: ' .
                            $e->getMessage();
            }
        } else {
            $customer_identifier = '';
            if (isset($customerData['email'])) {
                $customer_identifier = $customerData['email'];
            }
            $errorMsg = 'The login could not be completed, customer ' . $customer_identifier .
                        ' does not exist in Magento and the auto-provisioning' .
                        ' function is disabled';
        }
        $this->_processError($errorMsg);
    }

    private function provisionAddress($customerData, $customerId)
    {
        if ((isset($customerData['firstname']) && !empty($customerData['firstname'])) &&
            (isset($customerData['lastname']) && !empty($customerData['lastname'])) &&
            (isset($customerData['address']['telephone']) && !empty($customerData['address']['telephone'])) &&
            (isset($customerData['address']['street']) && !empty($customerData['address']['street'])) &&
            (isset($customerData['address']['city']) && !empty($customerData['address']['city'])) &&
            (isset($customerData['address']['country_code']) && !empty($customerData['address']['country_code'])) &&
            (isset($customerData['address']['postcode']) && !empty($customerData['address']['postcode']))
         ) {
            $address = $this->addressFactory->create();
            $address->setFirstname($customerData['firstname'])
                ->setLastname($customerData['lastname'])
                ->setCustomerId($customerId)
                ->setIsDefaultBilling('1')
                ->setIsDefaultShipping('1');

            if (isset($customerData['address']['street'])) {
                $address->setStreet($customerData['address']['street']);
            }

            if (isset($customerData['address']['company'])) {
                $address->setCompany($customerData['address']['company']);
            }

            if (isset($customerData['address']['city'])) {
                $address->setCity($customerData['address']['city']);
            }

            $address = $this->handleCountryAndRegion($customerData, $address);

            if (isset($customerData['address']['postcode'])) {
                $address->setPostcode($customerData['address']['postcode']);
            }

            if (isset($customerData['address']['telephone'])) {
                $address->setTelephone($customerData['address']['telephone']);
            }

            if (isset($customerData['address']['fax'])) {
                $address->setFax($customerData['address']['fax']);
            }

            $this->saveAddress($address);
        }
    }

    private function handleCountryAndRegion($customerData, $address)
    {
        $regionRequired = false;
        $country_id = null;
        if (isset($customerData['address']['country_code'])) {
            $country_code = $customerData['address']['country_code'];

            $countries = $this->countryCollectionFactory->create()
                            ->addCountryCodeFilter($country_code)
                            ->loadData()
                            ->toOptionArray(false);

            if (empty($countries)) {
                $countryId = $this->getCountryId($country_code);
                if (isset($countryId)) {
                    $countries = $this->countryCollectionFactory->create()
                            ->addCountryIdFilter($countryId)
                            ->loadData()
                            ->toOptionArray(false);
                }
            }

            if (!empty($countries)) {
                $country = $countries[0];
                $country_id = $country['value'];
                $address->setCountryId($country_id);
                // Need review
                if (isset($country['is_region_required'])) {
                    $regionRequired = $country['is_region_required'];
                } elseif (isset($country['is_region_visible'])) {
                    $regionRequired = $country['is_region_visible'];
                }
            }
        }

        if (isset($customerData['address']['region'])) {
            $regionData = $customerData['address']['region'];
            $address = $this->provisionRegion($address, $regionData, $regionRequired, $country_id);
        }

        return $address;
    }

    private function saveAddress($address)
    {
        try {
            $this->addressRepository->save($address);
            $this->messageManager->addSuccess(__('Customer address added.'));
        } catch (InputException $e) {
            $this->messageManager->addError($e->getMessage());
            foreach ($e->getErrors() as $error) {
                $this->messageManager->addError($error->getMessage());
            }
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Customer address can\'t be saved.'));
        }
    }

    private function provisionRegion($address, $regionData, $regionRequired, $country_id)
    {
        if ($regionRequired) {
            $regionModel = $this->regionFactory->create();

            $region = $regionModel->loadByCode($regionData, $country_id);
            $region_id = $region->getId();
            if (!isset($region_id)) {
                $region = $regionModel->loadByName($regionData, $country_id);
                $region_id = $region->getId();
            }

            if (isset($region_id)) {
                $address->setRegionId($region_id);
            }
        } else {
            $regions = $this->regionCollectionFactory->create()
                            ->addRegionCodeOrNameFilter($customerData['address']['region'])
                            ->loadData()
                            ->toOptionArray();

            if (!empty($regions)) {
                // 1st region is on position 1
                $address->setRegionId($regions[1]['value']);
                $address->setCountryId($regions[1]['country_id']);
            }
        }

        return $address;
    }

    /**
     * Update customer
     *
     */
    private function updateCustomer($customer, $customerData, $useCustomAttr)
    {
        $helper = $this->_getHelper();
        $updateCustomer = $helper->getConfig('pitbulk_saml2_customer/options/updateuser');
        if ($updateCustomer) {
            if (!empty($customerData['firstname'])) {
                $customer->setFirstname($customerData['firstname']);
            }
            if (!empty($customerData['lastname'])) {
                $customer->setLastname($customerData['lastname']);
            }
            if (!empty($customerData['groupid'])) {
                $customer->setGroupId($customerData['groupid']);
            }

            if ($useCustomAttr) {
                if (!empty($customerData['email'])) {
                    $customer->setEmail($customerData['email']);
                }

                $customAttrIdentifier =  $helper->getConfig('pitbulk_saml2_customer/custom_field_mapping/custom_attribute_mapping');
                if (isset($customerData['custom_attr'][$customAttrIdentifier])) {
                    unset($customerData['custom_attr'][$customAttrIdentifier]);
                }
            }

            if (isset($customerData['custom_attr'])) {
                // Custom Attr
                foreach ($customerData['custom_attr'] as $key => $value) {
                    $customer->setCustomAttribute($key, $value);
                }
            }
            $customer = $this->customerRepository->save($customer);
        }

        // If there is no address registered and SAMLResponse contains
        // address info, try register it
        if (!empty($customerData['address'])) {
            $addresses = $customer->getAddresses();
            if (empty($addresses)) {
                $this->provisionAddress($customerData, $customer->getId());
            }
        }

        return $customer;
    }

    /**
     * Register customer session
     *
     */
    private function registerCustomerSession($customerSession, $auth, $customer)
    {
        $customerSession->setCustomerDataAsLoggedIn($customer);
        $customerSession->regenerateId();

        $customerSession->setData('saml_login', true);
        $nameId = $auth->getNameId();
        $customerSession->setData('saml_nameid', $nameId);
        $nameIdFormat = $auth->getNameIdFormat();
        $customerSession->setData('saml_nameid_format', $nameIdFormat);
        $sessionIndex = $auth->getSessionIndex();
        $customerSession->setData('saml_sessionindex', $sessionIndex);
    }

    /**
     * Process SAML Attributes
     *
     */
    public function processAttrs($auth, $useCustomAttr = false)
    {
        $customerData = [
            'username' => '',
            'email' => '',
            'firstname' => '',
            'lastname' => '',
            'groupid' => ''
        ];

        $attrs = $auth->getAttributes();

        if (empty($attrs)) {
            if (!$useCustomAttr) {
                $customerData['email'] = $auth->getNameId();
            } else {
                $helper = $this->_getHelper();
                $customAttrIdentifier =  $helper->getConfig('pitbulk_saml2_customer/custom_field_mapping/custom_attribute_mapping');
                if (!empty($customAttrIdentifier)) {
                    $customerData['custom_attr'] = [
                        $customAttrIdentifier => $auth->getNameId()
                    ];
                }
            }
        } else {
            $mapping = $this->getAttrMapping();
            foreach (['username', 'email', 'firstname', 'lastname'] as $key) {
                if (!empty($mapping[$key]) && isset($attrs[$mapping[$key]])
                  && !empty($attrs[$mapping[$key]][0])) {
                    $customerData[$key] = $attrs[$mapping[$key]][0];
                }
            }

            $customerData = $this->addGroupData($customerData, $attrs, $mapping);

            $customerData = $this->addAddressData($customerData, $attrs, $mapping);

            $customerData = $this->addCustomAttributesData($customerData, $attrs);

            // If was not able to get the email by mapping,
            // assign then the NameId if it contains an @
            if (!isset($userData['email']) || empty($userData['email'])) {
                $nameId = $auth->getNameId();
                if (strpos($nameId, "@") !== false) {
                    $customerData['email'] = $nameId;
                }
            }
        }

        return $customerData;
    }

    /**
     * Aux method for assign group
     *
     */
    private function addGroupData($customerData, $attrs, $mapping)
    {
        if (!empty($mapping['group']) && isset($attrs[$mapping['group']])
          && !empty($attrs[$mapping['group']])) {
            $groupMapping = $this->getGroupMapping();
            $groupValues = $attrs[$mapping['group']];
            $groupid = $this->obtainGroupId($groupValues, $groupMapping);

            if ($groupid !== false) {
                $customerData['groupid'] = $groupid;
            }
        }

        return $customerData;
    }

    /**
     * Aux method for assign address
     *
     */
    private function addAddressData($customerData, $attrs, $mapping)
    {
        $customerData['address'] = [];

        if (isset($mapping['address'])) {
            foreach ($mapping['address'] as $key => $map) {
                if (empty($map) || !isset($attrs[$map])
                  || empty($attrs[$map][0])) {
                    continue;
                }

                if ($key == 'street1') {
                    $customerData['address']['street'][0] = $attrs[$map][0];
                } elseif ($key == 'street2') {
                    $customerData['address']['street'][1] = $attrs[$map][0];
                } else {
                    $customerData['address'][$key] = $attrs[$map][0];
                }
            }
        }

        return $customerData;
    }

    /**
     * Aux method for assign custom attributes
     *
     */
    private function addCustomAttributesData($customerData, $attrs)
    {
        $customMapping = $this->getCustomMapping();
        if (!empty($customMapping)) {
            $customerData['custom_attr'] = [];
            foreach ($customMapping as $key => $map) {
                if (isset($attrs[$map])) {
                    $customerData['custom_attr'][$key] = $attrs[$map][0];
                }
            }
            if (empty($customerData['custom_attr'])) {
                unset($customerData['custom_attr']);
            }
        }

        return $customerData;
    }

    /**
     * Aux method for get group mapping
     *
     */
    private function getGroupMapping()
    {
        $helper = $this->_getHelper();

        $groupMapping = [];
        for ($i=1; $i<11; $i++) {
            $key = 'pitbulk_saml2_customer/group_mapping/group'.$i;
            $maps = $helper->getConfig($key);
            $groupMapping[$i] = explode(',', $maps);
        }

        return $groupMapping;
    }

    /**
     * Aux method for get attribute mapping
     *
     */
    private function getAttrMapping()
    {
        $helper = $this->_getHelper();
        $mapping = [];

        $attrMapKey = 'pitbulk_saml2_customer/attr_mapping/';
        $addrMapKey = 'pitbulk_saml2_customer/address_mapping/';

        $mapping['username'] =  $helper->getConfig($attrMapKey.'username');
        $mapping['email'] =  $helper->getConfig($attrMapKey.'email');
        $mapping['firstname'] =  $helper->getConfig($attrMapKey.'firstname');
        $mapping['lastname'] =  $helper->getConfig($attrMapKey.'lastname');
        $mapping['group'] = $helper->getConfig($attrMapKey.'group');

        $addrMap = [];
        $addrMap['company'] = $helper->getConfig($addrMapKey.'company');
        $addrMap['street1'] = $helper->getConfig($addrMapKey.'street1');
        $addrMap['street2'] = $helper->getConfig($addrMapKey.'street2');
        $addrMap['city'] = $helper->getConfig($addrMapKey.'city');
        $addrMap['country_code'] = $helper->getConfig($addrMapKey.'country');
        $addrMap['region'] = $helper->getConfig($addrMapKey.'state');
        $addrMap['postcode'] = $helper->getConfig($addrMapKey.'zip');
        $addrMap['telephone'] = $helper->getConfig($addrMapKey.'telephone');
        $addrMap['fax'] = $helper->getConfig($addrMapKey.'fax');

        $mapping['address'] = $addrMap;

        return $mapping;
    }

    /**
     * Aux method for calculating groupid
     *
     */
    private function obtainGroupId($samlGroups, $groupValues)
    {
        foreach ($samlGroups as $samlGroup) {
            for ($i=1; $i<11; $i++) {
                if (in_array($samlGroup, $groupValues[$i])) {
                    return $i;
                }
            }
        }
        return false;
    }

    private function getCountryId($countryName)
    {
        $countries = [
            "Afghanistan" => "AF",
            "Åland Islands" => "AX",
            "Albania" => "AL",
            "Algeria" => "DZ",
            "American Samoa" => "AS",
            "Andorra" => "AD",
            "Angola" => "AO",
            "Anguilla" => "AI",
            "Antarctica" => "AQ",
            "Antigua and Barbuda",
            "Argentina" => "AR",
            "Armenia" => "AM",
            "Aruba" => "AW",
            "Australia" => "AU",
            "Austria" => "AT",
            "Azerbaijan" => "AZ",
            "Bahamas" => "BS",
            "Bahrain" => "BH",
            "Bangladesh" => "BD",
            "Barbados" => "BB",
            "Belarus" => "BY",
            "Belgium" => "BE",
            "Belize" => "BZ",
            "Benin" => "BJ",
            "Bermuda" => "BM",
            "Bhutan" => "BT",
            "Bolivia" => "BO",
            "Bosnia and Herzegovina" => "BA",
            "Botswana" => "BW",
            "Bouvet Island" => "BV",
            "Brazil" => "BR",
            "British Indian Ocean Territory" => "IO",
            "British Virgin Islands" => "VG",
            "Brunei" => "BN",
            "Bulgaria" => "BG",
            "Burkina Faso" => "BF",
            "Burundi" => "BI",
            "Cambodia" => "KH",
            "Cameroon" => "CM",
            "Canada" => "CA",
            "Cape Verde" => "CV",
            "Cayman Islands" => "KY",
            "Central African Republic" => "CF",
            "Chad" => "TD",
            "Chile" => "CL",
            "China" => "CN",
            "Christmas Island" => "CX",
            "Cocos (Keeling) Islands" => "CC",
            "Colombia" => "CO",
            "Comoros" => "KM",
            "Congo - Brazzaville" => "CG",
            "Congo - Kinshasa" => "CD",
            "Cook Islands" => "CK",
            "Costa Rica" => "CR",
            "Côte d’Ivoire" => "CI",
            "Croatia" => "HR",
            "Cuba" => "CU",
            "Cyprus" => "CY",
            "Czech Republic" => "CZ",
            "Denmark" => "DK",
            "Djibouti" => "DJ",
            "Dominica" => "DM",
            "Dominican Republic" => "DO",
            "Ecuador" => "EC",
            "Egypt" => "EG",
            "El Salvador" => "SV",
            "Equatorial Guinea" => "GQ",
            "Eritrea" => "ER",
            "Estonia" => "EE",
            "Ethiopia" => "ET",
            "Falkland Islands" => "FK",
            "Faroe Islands" => "FO",
            "Fiji" => "FJ",
            "Finland" => "FI",
            "France" => "FR",
            "French Guiana" => "GF",
            "French Polynesia" => "PF",
            "French Southern Territories" => "TF",
            "Gabon" => "GA",
            "Gambia" => "GM",
            "Georgia" => "GE",
            "Germany" => "DE",
            "Ghana" => "GH",
            "Gibraltar" => "GI",
            "Greece" => "GR",
            "Greenland" => "GL",
            "Grenada" => "GD",
            "Guadeloupe" => "GP",
            "Guam" => "GU",
            "Guatemala" => "GT",
            "Guernsey" => "GG",
            "Guinea" => "GN",
            "Guinea-Bissau" => "GW",
            "Guyana" => "GY",
            "Haiti" => "HT",
            "Honduras" => "HN",
            "Hong Kong SAR China" => "HK",
            "Hungary" => "HU",
            "Iceland" => "IS",
            "India" => "IN",
            "Indonesia" => "ID",
            "Iran" => "IR",
            "Iraq" => "IQ",
            "Ireland" => "IE",
            "Isle of Man" => "IM",
            "Israel" => "IL",
            "Italy" => "IT",
            "Jamaica" => "JM",
            "Japan" => "JP",
            "Jersey" => "JE",
            "Jordan" => "JO",
            "Kazakhstan" => "KZ",
            "Kenya" => "KE",
            "Kiribati" => "KI",
            "Kuwait" => "KW",
            "Kyrgyzstan" => "KG",
            "Laos" => "LA",
            "Latvia" => "LV",
            "Lebanon" => "LB",
            "Lesotho" => "LS",
            "Liberia" => "LR",
            "Libya" => "LY",
            "Liechtenstein" => "LI",
            "Lithuania" => "LT",
            "Luxembourg" => "LU",
            "Macau SAR China" => "MO",
            "Macedonia" => "MK",
            "Madagascar" => "MG",
            "Malawi" => "MW",
            "Malaysia" => "MY",
            "Maldives" => "MV",
            "Mali" => "ML",
            "Malta" => "MT",
            "Marshall Islands" => "MH",
            "Martinique" => "MQ",
            "Mauritania" => "MR",
            "Mauritius" => "MU",
            "Mayotte" => "YT",
            "Mexico" => "MX",
            "Micronesia" => "FM",
            "Moldova" => "MD",
            "Monaco" => "MC",
            "Mongolia" => "MN",
            "Montenegro" => "ME",
            "Montserrat" => "MS",
            "Morocco" => "MA",
            "Mozambique" => "MZ",
            "Myanmar (Burma)" => "MM",
            "Namibia" => "NA",
            "Nauru" => "NR",
            "Nepal" => "NP",
            "Netherlands" => "NL",
            "Netherlands Antilles" => "AN",
            "New Caledonia" => "NC",
            "New Zealand" => "NZ",
            "Nicaragua" => "NI",
            "Niger" => "NE",
            "Nigeria" => "NG",
            "Niue" => "NU",
            "Norfolk Island" => "NF",
            "Northern Mariana Islands" => "MP",
            "North Korea" => "KP",
            "Norway" => "NO",
            "Oman" => "OM",
            "Pakistan" => "PK",
            "Palau" => "PW",
            "Palestinian Territories" => "PS",
            "Panama" => "PA",
            "Papua New Guinea" => "PG",
            "Paraguay" => "PY",
            "Peru" => "PE",
            "Philippines" => "PH",
            "Pitcairn Islands" => "PN",
            "Poland" => "PL",
            "Portugal" => "PT",
            "Qatar" => "QA",
            "Réunion" => "RE",
            "Romania" => "RO",
            "Russia" => "RU",
            "Rwanda" => "RW",
            "Saint Barthélemy" => "BL",
            "Saint Helena" => "SH",
            "Saint Kitts and Nevis" => "KN",
            "Saint Lucia" => "LC",
            "Saint Martin" => "MF",
            "Saint Pierre and Miquelon" => "PM",
            "Samoa" => "WS",
            "San Marino" => "SM",
            "Saudi Arabia" => "SA",
            "Senegal" => "SN",
            "Serbia" => "RS",
            "Seychelles" => "SC",
            "Sierra Leone" => "SL",
            "Singapore" => "SG",
            "Slovakia" => "SK",
            "Slovenia" => "SI",
            "Solomon Islands" => "SB",
            "Somalia" => "SO",
            "South Africa" => "ZA",
            "South Korea" => "KR",
            "Spain" => "ES",
            "Sri Lanka" => "LK",
            "Sudan" => "SD",
            "Suriname" => "SR",
            "Svalbard and Jan Mayen" => "SJ",
            "Swaziland" => "SZ",
            "Sweden" => "SE",
            "Switzerland" => "CH",
            "Syria" => "SY",
            "Taiwan" => "TW",
            "Tajikistan" => "TJ",
            "Tanzania" => "TZ",
            "Thailand" => "TH",
            "Timor-Leste" => "TL",
            "Togo" => "TG",
            "Tokelau" => "TK",
            "Tonga" => "TO",
            "Trinidad and Tobago" => "TT",
            "Tunisia" => "TN",
            "Turkey" => "TR",
            "Turkmenistan" => "TM",
            "Turks and Caicos Islands" => "TC",
            "Tuvalu" => "TV",
            "Uganda" => "UG",
            "Ukraine" => "UA",
            "United Arab Emirates" => "AE",
            "United Kingdom" => "GB",
            "United States" => "US",
            "Uruguay" => "UY",
            "U.S. Outlying Islands" => "UM",
            "U.S. Virgin Islands" => "VI",
            "Uzbekistan" => "UZ",
            "Vanuatu" => "VU",
            "Vatican City" => "VA",
            "Venezuela" => "VE",
            "Vietnam" => "VN",
            "Wallis and Futuna" => "WF",
            "Western Sahara" => "EH",
            "Yemen" => "YE",
            "Zambia" => "ZM",
            "Zimbabwe" => "ZW"
        ];

        $countryId = null;
        if (isset($countries[$countryName])) {
            $countryId = $countries[$countryName];
        }
        return $countryId;
    }

    /**
     * Aux method for get custom mapping
     *
     */
    private function getCustomMapping()
    {
        $helper = $this->_getHelper();

        $keys = ["","_2","_3","_4"];

        $customAttrKey = 'pitbulk_saml2_customer/custom_field_mapping/';
        $customMapping = [];

        foreach ($keys as $key) {
            $customAttrCode =  $helper->getConfig($customAttrKey.'custom_attribute_code'.$key);
            $customAttrMapping =  $helper->getConfig($customAttrKey.'custom_attribute_mapping'.$key);

            if (isset($customAttrCode) && isset($customAttrMapping)) {
                $customMapping[$customAttrCode] = $customAttrMapping;
            }
        }

        return $customMapping;
    }
}
