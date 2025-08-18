<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Slub\LisztCommon\Controller\SearchController;
use Slub\LisztCommon\Routing\Aspect\DetailpageDocumentIdMapper;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['DetailpageDocumentIdMapper'] =
    DetailpageDocumentIdMapper::class;

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

// cache Detail Pages? not possible because of detail pages navigation (need context from search)
ExtensionUtility::configurePlugin(
    'LisztCommon',
    'SearchDetails',
    [ SearchController::class => 'details' ],
    [ SearchController::class => 'details' ],
);

// cache Detail Pages?
ExtensionUtility::configurePlugin(
    'LisztCommon',
    'SearchDetailsHeader',
    [ SearchController::class => 'detailsHeader' ],
    [ ],
);



ExtensionUtility::configurePlugin(
    'LisztCommon',
    'HtmxFilters',
    [SearchController::class => 'loadAllFilterItems'],
    []
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
