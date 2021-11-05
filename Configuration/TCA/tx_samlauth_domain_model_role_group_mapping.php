<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_role_group_mapping',
        'label' => 'role',
        'iconfile' => 'EXT:samlauth/Resources/Public/Icons/mapping.svg',
    ],
    'columns' => [
        'configuration' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_configuration',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_samlauth_domain_model_configuration',
            ]
        ],
        'role' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_samlauth_domain_model_role_group_mapping.role',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim,required'
            ]
        ],
        'group_ids' => [
            'label' => 'LLL:EXT:samlauth/Resources/Private/Language/locallang_db.xlf:tx_mksaml_auth.domain_model_group_mapping.group_ids',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
            ]
        ]
    ],
    'types' => [
        '0' => ['showitem' => 'configuration, role, group_ids'],
    ]
];
