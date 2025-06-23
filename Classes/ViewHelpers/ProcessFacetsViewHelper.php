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

        // Get selected items from _selected aggregation and add missing ones to main bucket
        $selectedItemsBucket = self::getSelectedItemsBucketFromAggregation($filterGroup, $key);
        $returnBucket = self::addMissingSelectedItems($returnBucket, $selectedItemsBucket);

        // Mark selected items based on URL parameters
        $returnBucket = self::markSelectedItems($returnBucket, $key, $searchParams);

        // if $searchParams['filterShowAll'] return for a single (htmx) filter with all items, sorted linguistic by alphabet
        if (isset($searchParams['filterShowAll']) && $searchParams['filterShowAll'] === $key) {
            // Create a German collator for linguistic sorting
            $collator = new \Collator('de_DE');

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

        // Get size and maxSize from settings
        $size = $filterEntities['size'] ?? $filterEntities['defaultFilterSize'] ?? 10;
        $maxSize = $filterEntities['maxSize'] ?? 30;

        // Remove items that are not selected and have a doc_count of 0
        $returnBucket = array_filter($returnBucket, function ($item) {
            $docCount = $item['doc_count'] ?? 0;
            $isSelected = $item['selected'] ?? false;

            return $docCount > 0 || $isSelected;
        });

        // Sort the array so that selected items come first
        $returnBucket = self::bringSelectedItemsToTop($returnBucket);

        foreach ($returnBucket as $index => &$item) {
            // if item is over $size set 'hidden' => true, but never hide selected items
            if ($index >= $size && !($item['selected'] ?? false)) {
                $item['hidden'] = true;
            }
        }
        unset($item);

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
     * Get selected items bucket from the _selected aggregation
     */
    private static function getSelectedItemsBucketFromAggregation(array $filterGroup, string $key): array
    {
        $selectedItems = [];
        $selectedKey = $key . '_selected';

        // Check for selected items on the same level as the main filter aggregation
        if (isset($filterGroup[$selectedKey]['buckets'])) {
            $selectedItems = $filterGroup[$selectedKey]['buckets'];
        }

        // Check for selected items in nested filter aggregation structure
        if (isset($filterGroup['filtered_params'][$selectedKey]['buckets'])) {
            $selectedItems = $filterGroup['filtered_params'][$selectedKey]['buckets'];
        }

        return $selectedItems;
    }

    /**
     * Add missing selected items from _selected bucket to main bucket
     */
    private static function addMissingSelectedItems(array $mainBucket, array $selectedItemsBucket): array
    {
        // Create array of existing keys in main bucket for quick lookup
        $existingKeys = array_column($mainBucket, 'key');

        // Add missing selected items to main bucket
        foreach ($selectedItemsBucket as $selectedItem) {
            $selectedKey = $selectedItem['key'] ?? null;
            if ($selectedKey !== null && !in_array($selectedKey, $existingKeys, true)) {
                // Add the missing selected item to the main bucket with correct doc_count from _selected aggregation
                $mainBucket[] = [
                    'key' => $selectedKey,
                    'doc_count' => $selectedItem['doc_count'] ?? 0
                ];
            }
        }

        return $mainBucket;
    }

    /**
     * Mark selected items in the bucket based on search parameters
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
     */
    private static function bringSelectedItemsToTop(array $bucket): array
    {
        usort($bucket, function ($a, $b) {
            return ($b['selected'] ?? false) <=> ($a['selected'] ?? false);
        });

        return $bucket;
    }

    /**
     * Get sum_other_doc_count from elasticsearch aggregation
     */
    private static function getSumOtherDocCount(array $filterGroup, string $key): int
    {
        // Check for nested filters (with filtered_params structure)
        if (isset($filterGroup['filtered_params'][$key]['sum_other_doc_count'])) {
            return (int)$filterGroup['filtered_params'][$key]['sum_other_doc_count'];
        }

        // Check for non-nested filters (direct structure)
        if (isset($filterGroup[$key]['sum_other_doc_count'])) {
            return (int)$filterGroup[$key]['sum_other_doc_count'];
        }

        return 0;
    }

}
