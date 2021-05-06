<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Zendesk in forms',
    'description' => 'Integrate ext:form with Zendesk. Requires Zendesk API credentials and depends on ext:bolius_zendesk.',
    'category' => 'misc',
    'author' => 'Bolius Digital',
    'author_email' => 'web@bolius.dk',
    'state' => 'beta',
    'uploadfolder' => 1,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-9.5.99',
            'form' => '',
            'bolius_zendesk' => '2.0.0-2.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
