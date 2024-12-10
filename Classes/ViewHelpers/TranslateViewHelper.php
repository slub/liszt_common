<?php
declare(strict_types=1);
namespace Slub\LisztCommon\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Slub\LisztCommon\Interfaces\ElasticSearchServiceInterface;
use Slub\LisztCommon\Services\ElasticSearchService;

final class TranslateViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('key', 'string', 'the key wich has to be translated', true);
        $this->registerArgument('index', 'string', 'the index which stores the translations', true);
        $this->registerArgument('locale', 'string', 'the language which should be returned', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext)
    : ?array
    {
        $term = GeneralUtility::makeInstanceService(ElasticSearchserviceInterface::class)->
            get($arguments['key'], $arguments['index']);

        return $term['hits']['hits']['_source'][$arguments['locale']];
    }
}
