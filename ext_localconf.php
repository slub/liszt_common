<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Slub\LisztCommon\Controller\SearchController;
use Slub\LisztCommon\Services\ElasticSearchService;

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

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('iconpack')) {
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \Quellenform\Iconpack\IconpackRegistry::class
    )->registerIconpack(
        'EXT:liszt_common/Configuration/Iconpack/LisztSearchResultsIconpack.yaml',
    );
}

ExtensionManagementUtility::addService(
    'liszt_common',
    'search',
    'tx_lisztcommon_search',
    [
        'title' => 'Elastic Search Service',
        'descripiton' => 'Provides a central interface for Elasticsearch operations',
        'subtype' => '',
        'available' => true,
        'priority' => 50,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'className' => ElasticSearchService::class
    ]
);
