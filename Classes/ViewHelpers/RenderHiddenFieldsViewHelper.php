<?php
namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to render nested arrays as hidden fields
 */
class RenderHiddenFieldsViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('data', 'array', 'The nested array to be converted to hidden fields', true);
        $this->registerArgument('namePrefix', 'string', 'Prefix for the field names', false, 'search');
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $data = $this->arguments['data'];
        $namePrefix = $this->arguments['namePrefix'];

        return $this->renderHiddenFieldsRecursive($data, $namePrefix);
    }

    /**
     * Recursively renders hidden fields for nested arrays
     *
     * @param mixed $data The data to convert
     * @param string $namePrefix The current field name prefix
     * @return string
     */
    protected function renderHiddenFieldsRecursive(mixed $data, string $namePrefix): string
    {
        $result = '';

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $currentName = $namePrefix . '[' . $key . ']';

                if (is_array($value)) {
                    // Recursive call for nested arrays
                    $result .= $this->renderHiddenFieldsRecursive($value, $currentName);
                } else {
                    // Render a hidden field for scalar values
                    $result .= '<input type="hidden" name="' . htmlspecialchars($currentName) . '" value="' . htmlspecialchars($value) . '" />' . PHP_EOL;
                }
            }
        } else {
            // Handle non-array values directly
            $result .= '<input type="hidden" name="' . htmlspecialchars($namePrefix) . '" value="' . htmlspecialchars($data) . '" />' . PHP_EOL;
        }

        return $result;
    }
}
