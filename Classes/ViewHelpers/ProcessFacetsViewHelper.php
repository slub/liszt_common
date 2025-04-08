<?php
declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class ProcessFacetsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('filterGroup', 'array', 'Array with facets from solr', true);
        $this->registerArgument('key', 'string', 'key of the current facet', true);
        $this->registerArgument('searchParams', 'array', 'Array with search params from url', true);
        $this->registerArgument('filterEntities', 'array', 'Array with settings for filters from setup.typoscript', false, []);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext): array
    {

        $filterGroup = $arguments['filterGroup'];
        $key = $arguments['key'];
        $searchParams = $arguments['searchParams'];
        $returnBucket = [];
        $filterEntities = $arguments['filterEntities'];

        // check if buckets exists on "normal" not nested filters
        if (isset($filterGroup[$key]['buckets'])) {
            $returnBucket = $filterGroup[$key]['buckets'];
        }

        // check buckets on nested filters
        if (isset($filterGroup['filtered_params'][$key]['buckets'])) {
            $returnBucket = $filterGroup['filtered_params'][$key]['buckets'];
        }

        // if this is an range filter return a simple assoziative array
        if ($filterEntities['select'] === 'range') {
            $rangeBucket = [];
            foreach ($returnBucket as $item) {
                if (isset($item['key']) && isset($item['doc_count'])) {
                    $rangeBucket[$item['key']] = $item['doc_count'];
                }
            }
            return $rangeBucket;
        }

        // set size from entity settings in setup.typoscript or use 10 as default
        $size = $filterEntities['size'] ?? $filterEntities['defaultFilterSize'] ?? 10;


        // find active filter items and set selected and set hidden for items over size
        foreach ($returnBucket as $index => &$item) {
            $filterKey = $item['key'] ?? null;
            $item['selected'] = isset($searchParams['filter'][$key][$filterKey])
                && $searchParams['filter'][$key][$filterKey] == 1;

            // if item is over $size set 'hidden' => true
            if ($index >= $size && !$item['selected']) {
                $item['hidden'] = true;
            }
        }
        unset($item);


        // Remove items that are not selected and have a doc_count of 0
        $returnBucket = array_filter($returnBucket, function ($item) {
            return $item['doc_count'] > 0 || ($item['selected'] ?? false);
        });

        // Sort the array so that selected items come first
        usort($returnBucket, function ($a, $b) {
            return ($b['selected'] ?? false) <=> ($a['selected'] ?? false);
        });


        // special sort in "slub-style" if sortByKey = 'slub': sort all hidden items alphabetical
        if (isset($filterEntities['sortByKey']) && $filterEntities['sortByKey'] === 'slub') {
            $hiddenItems = [];
            $visibleItems = [];

            foreach ($returnBucket as $item) {
                if (isset($item['hidden']) && $item['hidden'] === true) {
                    $hiddenItems[] = $item;
                } else {
                    $visibleItems[] = $item;
                }
            }

            usort($hiddenItems, function ($a, $b) {
                return strnatcasecmp($a['key'] ?? '', $b['key'] ?? '');
            });

            $returnBucket = array_merge($visibleItems, $hiddenItems);
        }


        return $returnBucket;

    }
}
