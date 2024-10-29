<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Slub\LisztCommon\Controller\SearchController;

defined('TYPO3') or die();


// configure Search Listing Plugin, disable caching so that the search terms entered are updated and not the entire search-page was cached in page cache
ExtensionUtility::configurePlugin(
    'LisztCommon',
    'SearchListing',
    [ SearchController::class => 'index' ],
    [ SearchController::class => 'index' ]
);

// configure Search Listing Plugin, disable caching so that the search terms entered are updated and not the entire search-page was cached in page cache
ExtensionUtility::configurePlugin(
    'LisztCommon',
    'SearchBar',
    [ SearchController::class => 'searchBar' ],
    [ SearchController::class => 'searchBar' ],
);

ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:liszt_common/Configuration/TsConfig/page.tsconfig">'
);
