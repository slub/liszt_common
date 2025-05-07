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

        // if $searchParams['filterShowAll'] return for a single (htmx) filter with all items, sorted linguistic by alphabet
        if (isset($searchParams['filterShowAll']) && $searchParams['filterShowAll'] === $key) {
            // Create a German collator for linguistic sorting
            $collator = new \Collator('de_DE');

            // Mark selected items
            $returnBucket = self::markSelectedItems($returnBucket, $key, $searchParams);

            // First sort by key using the German collator
            $sortArray = [];
            foreach ($returnBucket as $index => $item) {
                $sortArray[$index] = $item['key'] ?? '';
            }

            // Sort the array keys based on the German collation
            $collator->asort($sortArray);

            // Create a new sorted array based on the sorted keys
            $sortedBucket = [];
            foreach ($sortArray as $index => $value) {
                $sortedBucket[] = $returnBucket[$index];
            }

            // Now sort again to bring selected items to the top
            $sortedBucket = self::bringSelectedItemsToTop($sortedBucket);

            return $sortedBucket;
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
        $returnBucket = self::bringSelectedItemsToTop($returnBucket);

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

    /**
     * Mark selected items in the bucket based on search parameters
     *
     * @param array $bucket The array of items to process
     * @param string $key The key of the current facet
     * @param array $searchParams The search parameters from URL
     * @return array The processed bucket with selected items marked
     */
    private static function markSelectedItems(array $bucket, string $key, array $searchParams): array
    {
        foreach ($bucket as &$item) {
            $filterKey = $item['key'] ?? null;
            $item['selected'] = isset($searchParams['filter'][$key][$filterKey])
                && $searchParams['filter'][$key][$filterKey] == 1;
        }
        unset($item);

        return $bucket;
    }

    /**
     * Sort array to bring selected items to the top
     *
     * @param array $bucket The array of items to sort
     * @return array The sorted array with selected items at the top
     */
    private static function bringSelectedItemsToTop(array $bucket): array
    {
        usort($bucket, function ($a, $b) {
            return ($b['selected'] ?? false) <=> ($a['selected'] ?? false);
        });

        return $bucket;
    }
}
