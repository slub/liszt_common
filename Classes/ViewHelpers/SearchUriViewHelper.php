<?php
declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

// we use this view helper for URL's because f:link creates URL's with controller and action in URL.
class SearchUriViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('searchParams', 'array', 'Search parameters', true);
        $this->registerArgument('pageUid', 'int', 'Target page UID', false);
        $this->registerArgument('namespace', 'string', 'Custom namespace for URL parameters instead of tx_liszt_common_searchlisting', false, 'search');
        // Note: The namespace change only applies to links generated with this ViewHelper.
        // For forms like the SearchBar, the namespace is configured in setup TypoScript.

        //   $this->registerArgument('removeCHash', 'bool', 'Remove cHash', false, false);
    }

    public function render(): string
    {
        $searchParams = $this->arguments['searchParams'];
        $pageUid = $this->arguments['pageUid'];
        $namespace = $this->arguments['namespace'];
        //   $removeCHash = $this->arguments['removeCHash'];

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->reset();

        if ($pageUid) {
            $uriBuilder->setTargetPageUid($pageUid);
        }


        $additionalParams = [
            $namespace => $searchParams
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
