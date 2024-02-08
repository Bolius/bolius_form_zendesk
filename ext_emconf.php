<?php

$EM_CONF['bolius_form_zendesk'] = [
    'title'        => 'Zendesk in forms',
    'description'  => 'Integrate ext:form with Zendesk. Requires Zendesk API credentials and depends on ext:bolius_zendesk.',
    'category'     => 'misc',
    'author'       => 'Bolius Digital',
    'author_email' => 'web@bolius.dk',
    'state'        => 'beta',
    'version'      => '1.0.0',
    'constraints'  => [
        'depends'   => [
            'typo3'          => '6.0.0-11.99.99',
            'form'           => '',
            'bolius_zendesk' => '2.0.0-2.99.99'
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
];