<?php

$EM_CONF[$_EXTKEY] = [
  'title' => 'SAML Authentication',
  'description' => 'Authentication for SAML IDP',
  'version' => '1.0.0',
  'state' => 'stable',
  'uploadfolder' => false,
  'clearcacheonload' => false,
  'category' => 'misc',
  'author' => 'Sven Wappler',
  'author_email' => 'info@wappler.systems',
  'author_company' => 'WapplerSystems',
  'constraints' => [
    'depends' => [
      'typo3' => '8.7.0-8.7.99',
    ],
    'conflicts' => [],
    'suggests' => [],
  ],
  '_md5_values_when_last_written' => '',
  'createDirs' => '',
];

