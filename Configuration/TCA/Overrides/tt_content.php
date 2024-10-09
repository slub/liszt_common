<?php


use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


defined('TYPO3') or die();

// register search Plugin
ExtensionUtility::registerPlugin(
    'liszt_common',
    'SearchListing',
    'Liszt Search Results'
);


// Adds the content element to the "Type" dropdown
ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' =>  'LLL:EXT:liszt_common/Resources/Private/Language/locallang.xlf:listing_title',
        'value' => 'lisztcommon_searchlisting',
        'icon' => 'content-text',
        'group' => 'plugins',
        'description' => 'LLL:EXT:liszt_common/Resources/Private/Language/locallang.xlf:listing_description'
    ]
);

// configure the backend form fields for SearchList Plugin (no extra fields needed)
$GLOBALS['TCA']['tt_content']['types']['lisztcommon_searchlisting'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
           --palette--;;general,
           header; Title,
           bodytext;LLL:EXT:core/Resources/Private/Language/Form/locallang_ttc.xlf:bodytext_formlabel,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
           --palette--;;hidden,
           --palette--;;acces,
        ',
    'columnsOverrides' => [
        'bodytext' => [
            'config' => [
                'enableRichtext' => true,
                'richtextConfiguration' => 'default'
            ]
        ]
    ]
];
