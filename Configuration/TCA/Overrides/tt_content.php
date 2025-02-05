<?php
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


defined('TYPO3') or die();


// register search Results Plugin
ExtensionUtility::registerPlugin(
    'liszt_common',
    'SearchListing',
    'Liszt Search Results'
);

// register search Bar Plugin
ExtensionUtility::registerPlugin(
    'liszt_common',
    'SearchBar',
    'Liszt Search Bar'
);

ExtensionUtility::registerPlugin(
    'liszt_common',
    'SearchDetails',
    'Liszt Search Details'
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

// Adds the content element to the "Type" dropdown
ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' =>  'LLL:EXT:liszt_common/Resources/Private/Language/locallang.xlf:searchbar_title',
        'value' => 'lisztcommon_searchbar',
        'icon' => 'content-text',
        'group' => 'plugins',
        'description' => 'LLL:EXT:liszt_common/Resources/Private/Language/locallang.xlf:searchbar_description'
    ]
);

ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' =>  'LLL:EXT:liszt_common/Resources/Private/Language/locallang.xlf:searchdetails_title',
        'value' => 'lisztcommon_searchdetails',
        'icon' => 'content-text',
        'group' => 'plugins',
        'description' => 'LLL:EXT:liszt_common/Resources/Private/Language/locallang.xlf:searchdetails_description'
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

// configure the backend form fields for SearchBar Plugin (no extra fields needed)
$GLOBALS['TCA']['tt_content']['types']['lisztcommon_searchbar'] = [
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


// configure the backend form fields for SearchDetails Plugin (no extra fields needed)
$GLOBALS['TCA']['tt_content']['types']['lisztcommon_searchdetails'] = [
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
