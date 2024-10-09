<?php

declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Type\File\ImageInfo;



final class GetQueryParamsViewHelper extends AbstractViewHelper
{


    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): array
    {

        /** @var RenderingContext $renderingContext */
        $request = $renderingContext->getRequest();


        return $request->getQueryParams();

    }
}
