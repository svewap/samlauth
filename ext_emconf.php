<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'SAML Authentication',
    'description' => 'Authentication for SAML IDP',
    'version' => '1.0.0',
    'state' => 'stable',
    'category' => 'misc',
    'author' => 'Sven Wappler',
    'author_email' => 'info@wappler.systems',
    'author_company' => 'WapplerSystems',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];

