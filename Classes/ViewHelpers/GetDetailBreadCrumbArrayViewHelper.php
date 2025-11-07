<?php
declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class GetDetailBreadCrumbArrayViewHelper extends AbstractViewHelper
{

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext)
    : ?array
    {

        $request = $renderingContext->getRequest();$frontendController = $request->getAttribute('frontend.controller');
        $rootline = $frontendController->rootLine;
        return array_reverse($rootline);
    }
}
