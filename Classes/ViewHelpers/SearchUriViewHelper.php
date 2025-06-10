<?php
declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class SearchUriViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('searchParams', 'array', 'Search parameters', true);
        $this->registerArgument('pageUid', 'int', 'Target page UID', false);
     //   $this->registerArgument('removeCHash', 'bool', 'Remove cHash', false, false);
        $this->registerArgument('wrapInSearchParams', 'bool', 'Wrap parameters in searchParams key for backward compatibility', false, true);
    }

    public function render(): string
    {
        $searchParams = $this->arguments['searchParams'];
        $pageUid = $this->arguments['pageUid'];
     //   $removeCHash = $this->arguments['removeCHash'];
        $wrapInSearchParams = $this->arguments['wrapInSearchParams'];

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->reset();

        if ($pageUid) {
            $uriBuilder->setTargetPageUid($pageUid);
        }

        // Wrap parameters in 'searchParams' for backward compatibility with existing controller
        $pluginParams = $wrapInSearchParams ? ['searchParams' => $searchParams] : $searchParams;

        $additionalParams = [
            'tx_liszt_common_searchlisting' => $pluginParams
        ];

        $uri = $uriBuilder
            ->setArguments($additionalParams)
            ->setCreateAbsoluteUri(false)
            ->setAddQueryString(false)
            ->build();

        // cHash would be disabled in Settings->Configure Installation-Wide Options->[FE][cacheHash][excludedParameters] -> ^tx_liszt_common_searchlisting[

        // Remove cHash if not wanted
//       if ($removeCHash && str_contains($uri, 'cHash=')) {
//            $uri = preg_replace('/&cHash=[^&]*/', '', $uri);
//            $uri = preg_replace('/\?cHash=[^&]*&/', '?', $uri);
//            $uri = preg_replace('/\?cHash=[^&]*$/', '', $uri);
//        }

        return $uri;
    }
}
