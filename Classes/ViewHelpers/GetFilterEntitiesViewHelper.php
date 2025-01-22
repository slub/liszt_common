<?php
declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class GetFilterEntitiesViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('entityTypes', 'array', 'The settings.entityTypes array', true);
        $this->registerArgument('filterKey', 'string', 'The field key to filter for', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext): array
    {
        $entityTypes = $arguments['entityTypes'];
        $filterKey = $arguments['filterKey'];

        foreach ($entityTypes as $entityType) {
            if (!empty($entityType['filters']) && is_array($entityType['filters'])) {
                foreach ($entityType['filters'] as $filter) {
                    if (!empty($filter['field']) && $filter['field'] === $filterKey) {
                        return array_merge($entityType, $filter);
                    }
                }
            }
        }

        return []; //if nothing is found
    }
}
