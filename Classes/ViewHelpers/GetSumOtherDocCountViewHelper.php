<?php
declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class GetSumOtherDocCountViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('filterGroup', 'array', 'Array with facets from elasticsearch', true);
        $this->registerArgument('key', 'string', 'key of the current facet', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): int
    {
        $filterGroup = $arguments['filterGroup'];
        $key = $arguments['key'];

        // Check for nested filters (with filtered_params structure)
        if (isset($filterGroup['filtered_params'][$key]['sum_other_doc_count'])) {
            return (int)$filterGroup['filtered_params'][$key]['sum_other_doc_count'];
        }

        // Check for non-nested filters (direct structure)
        if (isset($filterGroup[$key]['sum_other_doc_count'])) {
            return (int)$filterGroup[$key]['sum_other_doc_count'];
        }

        // Return 0 if not found
        return 0;
    }
}
