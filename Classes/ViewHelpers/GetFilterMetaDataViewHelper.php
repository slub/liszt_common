<?php

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class GetFilterMetaDataViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('filterGroup', 'array', 'Array with facets from solr', true);
        $this->registerArgument('key', 'string', 'key of the current facet', true);
        $this->registerArgument('buckets', 'array', 'Processed buckets array', true);
        $this->registerArgument('filterEntities', 'array', 'Array with settings for filters', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext): array
    {
        $filterGroup = $arguments['filterGroup'];
        $key = $arguments['key'];
        $buckets = $arguments['buckets'];
        $filterEntities = $arguments['filterEntities'];

        $size = $filterEntities['size'] ?? $filterEntities['defaultFilterSize'] ?? 10;
        $sumOtherDocCount = self::getSumOtherDocCount($filterGroup, $key);

        return [
            'hasMoreThanSize' => count($buckets) > $size,
            'hasMoreThanMaxSize' => $sumOtherDocCount > 0,
            'totalCount' => count($buckets),
            'sumOtherDocCount' => $sumOtherDocCount
        ];
    }

    private static function getSumOtherDocCount(array $filterGroup, string $key): int
    {
        // Check for nested filters
        if (isset($filterGroup['filtered_params'][$key]['sum_other_doc_count'])) {
            return (int)$filterGroup['filtered_params'][$key]['sum_other_doc_count'];
        }

        // Check for non-nested filters
        if (isset($filterGroup[$key]['sum_other_doc_count'])) {
            return (int)$filterGroup[$key]['sum_other_doc_count'];
        }

        return 0;
    }
}
