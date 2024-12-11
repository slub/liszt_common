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

        switch ($action) {
            case 'add':
                $searchParamsArray[$key] = $value;
                break;

            case 'remove':
                unset($searchParamsArray[$key]);
                break;
            // later here are special values possible like "disableFilter" with value=0
            case 'addFilter':
                $searchParamsArray['filter'][$key][$value] = 1;
                break;

            case 'removeFilter':
                unset($searchParamsArray['filter'][$key][$value]);
                break;

            default:
                break;
        }

        // Convert the array to a string formatted as {key: 'value', key2: 'value2'}
        /*
        $formattedParams = [];
        foreach ($searchParamsArray as $paramKey => $paramValue) {
            $formattedParams[] = "{$paramKey}: '" . $paramValue . "'";
        }
        */

       //  return '{' . implode(', ', $formattedParams) . '}';
       return ['searchParams' => $searchParamsArray];

    }
}
