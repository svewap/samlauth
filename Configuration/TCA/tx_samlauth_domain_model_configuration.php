<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration',
        'label' => 'name',
        'iconfile' => 'EXT:samlauth/Resources/Public/Icons/config.svg',
    ],
    'columns' => [
        'name' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.item_name',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim,required'
            ]
        ],
        'url' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.item_url',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ]
        ],
        'domain' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.item_domain',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => 'WapplerSystems\Samlauth\Backend\DomainProvider->addItems',
                'min' => 1,
            ]
        ],
        'debug' => [
            'exclude' => 1,
            'label' => 'Debug ',
            'config' => [
                'type' => 'check',
                'items' => [
                    [ 'yes', '' ],
                ],
            ]
        ],
        'user_folder' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.item_user_folder',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1,
            ]
        ],
        'certificate' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.item_certificate',
            'config' => [
                'type' => 'text',
                'eval' => 'required'
            ]
        ],
        'idp_entity_id' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.item_idp_entity_id',
            'config' => [
                'type' => 'input',
                'eval' => 'required'
            ]
        ],
        'idp_certificate' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.item_idp_certificate',
            'config' => [
                'type' => 'text',
                'eval' => 'required'
            ]
        ],
        'cert_key' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.item_key',
            'config' => [
                'type' => 'text',
            ]
        ],
        'passphrase' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.item_passphrase',
            'config' => [
                'type' => 'input',
                'eval' => 'password,trim'
            ]
        ],
        'default_groups_enable' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.default_groups_enable',
            'config' => [
                'type' => 'check',
            ]
        ],
        'default_groups' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.default_groups',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
            ]
        ],
        'sp_acs_page' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.sp_acs_page',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
            ],
        ],
        'sp_sls_page' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.sp_sls_page',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
            ],
        ],
        'sso_binding' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.sso_binding',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['HTTP-POST', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'],
                ],
            ],
        ],
        'role_group_mappings' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration.role_group_mappings',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_samlauth_domain_model_role_group_mapping',
                'foreign_field' => 'configuration',
                'maxitems' => 10,
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ],
            ],
        ],

    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:samlauth/Resources/Private/Language/locallang_tabs.xlf:general,
                    domain,user_folder,debug,sso_binding,role_group_mappings,
                --div--;LLL:EXT:samlauth/Resources/Private/Language/locallang_tabs.xlf:sp,
                    name,certificate,cert_key,passphrase,sp_acs_page,sp_sls_page,
                --div--;LLL:EXT:samlauth/Resources/Private/Language/locallang_tabs.xlf:idp,
                    idp_entity_id,url,idp_certificate,
                --div--;LLL:EXT:samlauth/Resources/Private/Language/locallang_tabs.xlf:features,
                    default_groups_enable,default_groups
            '],
    ]
];
