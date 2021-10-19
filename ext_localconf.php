<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use WapplerSystems\Samlauth\Authentication\SamlAuth;
use WapplerSystems\Samlauth\Controller\AuthController;
use WapplerSystems\Samlauth\Enricher\DefaultAttributesEnricher;
use WapplerSystems\Samlauth\Enricher\DefaultGroupEnricher;
use WapplerSystems\Samlauth\Enricher\DummyPasswordEnricher;
use WapplerSystems\Samlauth\Enricher\RoleGroupMapperEnricher;
use WapplerSystems\Samlauth\Enricher\SamlHostnameEnricher;
use WapplerSystems\Samlauth\Enricher\SimpleAttributeEnricher;
use WapplerSystems\Samlauth\EnricherRegistry;

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function ($extKey) {


        ExtensionUtility::configurePlugin(
            'WapplerSystems.samlauth',
            'metadata',
            [
                AuthController::class => 'metadata'
            ],
            [
                AuthController::class => 'metadata'
            ]
        );

        ExtensionUtility::configurePlugin(
            'WapplerSystems.samlauth',
            'sls',
            [
                AuthController::class => 'singleLogoutService'
            ],
            [
                AuthController::class => 'singleLogoutService'
            ]
        );


        ExtensionUtility::configurePlugin(
            'WapplerSystems.samlauth',
            'auth',
            [
                AuthController::class => 'auth'
            ],
            [
                AuthController::class => 'auth'
            ]
        );


        ExtensionManagementUtility::addService(
            $extKey,
            'auth',
            SamlAuth::class,
            [
                'title' => 'Saml-Auth FE-User',
                'description' => 'Authenticates FE-Users/groups via Saml',
                'subtype' => 'authUserFE,getUserFE',
                'available' => true,
                'priority' => 70,
                'quality' => 70,
                'os' => '',
                'exec' => '',
                'className' => SamlAuth::class,
            ]
        );

        /* auth service getUser von sv deaktivieren */
        unset($GLOBALS['T3_SERVICES']['auth']['TYPO3\CMS\Core\Authentication\AuthenticationService']['serviceSubTypes']['getUserFE']);
        unset($GLOBALS['T3_SERVICES']['auth']['TYPO3\CMS\Core\Authentication\AuthenticationService']['serviceSubTypes']['processLoginDataFE']);

        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;


        if (class_exists(DummyPasswordEnricher::class)) {
            EnricherRegistry::register(DefaultAttributesEnricher::class, 100);
            EnricherRegistry::register(DummyPasswordEnricher::class, 100);
            EnricherRegistry::register(SamlHostnameEnricher::class, 100);
            EnricherRegistry::register(SimpleAttributeEnricher::class);
            EnricherRegistry::register(DefaultGroupEnricher::class);
            EnricherRegistry::register(RoleGroupMapperEnricher::class);
        }
    },
    'samlauth'
);
