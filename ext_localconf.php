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
            'acs',
            [
                'Auth' => 'assertionConsumerService'
            ],
            [
                'Auth' => 'assertionConsumerService'
            ]
        );


        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'WapplerSystems.samlauth',
            'loginButton',
            [
                'Login' => 'button'
            ],
            [
                'Login' => 'button'
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
        /* always fetch */
        //$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;

        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = true;

    },
    'samlauth'
);