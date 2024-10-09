<?php
declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class GetQueryParamsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('param', 'string', 'Name of the parameter key', true);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): ?string
    {
        $request = $renderingContext->getRequest();
        $queryParams = $request->getQueryParams();

        // Check if the argument param exists in the query parameters
        if (array_key_exists($arguments['param'], $queryParams)) {
            return $queryParams[$arguments['param']];
        }

        // Return null if parameter does not exist
        return null;
    }
}
