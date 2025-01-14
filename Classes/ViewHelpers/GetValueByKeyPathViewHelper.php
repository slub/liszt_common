<?php
declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/*
 * This ViewHelper ist for returning object keys wich have an point in her name
 * uses in facets to display right filter item names
*/
class GetValueByKeyPathViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments for the ViewHelper
     */
    public function initializeArguments()
    {
        $this->registerArgument('data', 'array', 'The array to search in', true);
        $this->registerArgument('keys', 'array', 'An array of keys defining the path to the desired value', true);
    }

    /**
     * Resolve a value in a deeply nested array by following an array of keys
     *
     * @return mixed|null
     */
    public function render()
    {
        $data = $this->arguments['data'];
        $keys = $this->arguments['keys'];

        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                // Key does not exist, return null
                return null;
            }
        }

        return $data;
    }
}
