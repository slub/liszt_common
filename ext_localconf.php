<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Slub\LisztCommon\Controller\SearchController;

defined('TYPO3') or die();


// Attention! ToDo: research for disable Cache for this Controller and Searchbar
ExtensionUtility::configurePlugin(
    'LisztCommon',
    'SearchListing',
    [ SearchController::class => 'index' ],
    [ SearchController::class => 'index' ]
);

ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:liszt_common/Configuration/TsConfig/page.tsconfig">'
);
