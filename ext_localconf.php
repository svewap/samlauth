<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function ($extKey) {


        require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extKey).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Private'.DIRECTORY_SEPARATOR.'PHP'.DIRECTORY_SEPARATOR.'autoload.php';

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'WapplerSystems.samlauth',
            'metadata',
            [
                'Auth' => 'metadata'
            ],
            [
                'Auth' => 'metadata'
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'WapplerSystems.samlauth',
            'sls',
            [
                'Auth' => 'singleLogoutService'
            ],
            [
                'Auth' => 'singleLogoutService'
            ]
        );


        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'WapplerSystems.samlauth',
            'auth',
            [
                'Auth' => 'auth'
            ],
            [
                'Auth' => 'auth'
            ]
        );



        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
            $extKey,
            'auth',
            \WapplerSystems\Samlauth\Authentication\SamlAuth::class,
            [
                'title' => 'Saml-Auth FE-User',
                'description' => 'Authenticates FE-Users/groups via Saml',
                'subtype' => 'authUserFE,getUserFE',
                'available' => true,
                'priority' => 70,
                'quality' => 70,
                'os' => '',
                'exec' => '',
                'className' => \WapplerSystems\Samlauth\Authentication\SamlAuth::class,
            ]
        );

        /* auth service getUser von sv deaktivieren */
        unset($GLOBALS['T3_SERVICES']['auth']['TYPO3\CMS\Sv\AuthenticationService']['serviceSubTypes']['getUserFE']);

        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = true;


        if (class_exists(\WapplerSystems\Samlauth\Enricher\DummyPasswordEnricher::class)) {
            \WapplerSystems\Samlauth\EnricherRegistry::register(\WapplerSystems\Samlauth\Enricher\DefaultAttributesEnricher::class, 100);
            \WapplerSystems\Samlauth\EnricherRegistry::register(\WapplerSystems\Samlauth\Enricher\DummyPasswordEnricher::class, 100);
            \WapplerSystems\Samlauth\EnricherRegistry::register(\WapplerSystems\Samlauth\Enricher\SamlHostnameEnricher::class, 100);
            \WapplerSystems\Samlauth\EnricherRegistry::register(\WapplerSystems\Samlauth\Enricher\SimpleAttributeEnricher::class);
            \WapplerSystems\Samlauth\EnricherRegistry::register(\WapplerSystems\Samlauth\Enricher\DefaultGroupEnricher::class);
            \WapplerSystems\Samlauth\EnricherRegistry::register(\WapplerSystems\Samlauth\Enricher\RoleGroupMapperEnricher::class);
        }
    },
    'samlauth'
);