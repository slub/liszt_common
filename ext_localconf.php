<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Slub\LisztCommon\Controller\SearchController;

defined('TYPO3') or die();

# this is not needed at the moment because we handle the search Bar in liszt_bibliography
# create search controller and search actions
/*ExtensionUtility::configurePlugin(
    'LisztCommon',
    'SearchBar',
    [
        SearchController::class => 'index',
    ]
);*/

// not needed in Typo v13 because page.tsconfig is auto included with page.tsconfig
/*ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:liszt_common/Configuration/TsConfig/page.tsconfig">'
);*/
