<?php
declare(strict_types=1);
namespace Slub\LisztCommon\ViewHelpers;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class SearchParamsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('action', 'string', 'the operation to be performed with the search parameters', true);
        $this->registerArgument('key', 'string', 'the key wich has to be operated (added oder removed) ', true);
        $this->registerArgument('value', 'string', 'the value if is a add operation', false);
        $this->registerArgument('searchParamsArray', 'array', 'the Array with SearchParams from Controller', true);
    }
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext)
    : ?array
    {
        $action = $arguments['action'];
        $key = $arguments['key'];
        $value = $arguments['value'] ?? null;
        $searchParamsArray = $arguments['searchParamsArray'];

        if ($action === 'add') {
            $searchParamsArray[$key] = $value;
        } elseif ($action === 'remove') {
            unset($searchParamsArray[$key]);
        }

        // Convert the array to a string formatted as {key: 'value', key2: 'value2'}
        $formattedParams = [];
        foreach ($searchParamsArray as $paramKey => $paramValue) {
            $formattedParams[] = "{$paramKey}: '" . $paramValue . "'";
        }

       //  return '{' . implode(', ', $formattedParams) . '}';
        return ['searchParams' => $searchParamsArray];

    }
}
