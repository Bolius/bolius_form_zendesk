<?php

/**
 * Configure logger
 */
$GLOBALS['TYPO3_CONF_VARS']['LOG']['Bolius']['BoliusFormZendesk'] = [
    'writerConfiguration' => [
        \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
            'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => [
                'logFile' => 'typo3temp/logs/Bolius.BoliusFormZendesk.log'
            ]
        ],
        \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
            'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => [
                'logFile' => 'typo3temp/logs/Bolius.BoliusFormZendesk.error.log'
            ]
        ]
    ]
];

require_once (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('bolius_form_zendesk')
    . 'Resources/Private/Libraries/Html2Text/Html2Text.php');