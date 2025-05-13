<?php
namespace Slub\LisztCommon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to render dt Element with comma separated fields and optional prefix for Detail View
 */
class CommaSeparatedListElementViewHelper extends AbstractViewHelper
{

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('values', 'array', 'The values to join', true);
        $this->registerArgument('label', 'string', 'label for dt Element', true);
    }

    public function render(): string
    {
        $values = $this->arguments['values'];
        $label = $this->arguments['label'];

        $nonEmptyValues = array_filter($values, function($value) {
            return $value !== null && $value !== '';
        });

        $html = '';

        if (empty($nonEmptyValues)) return $html;

        $formattedValues = [];
        $previousKey = null;

        foreach ($nonEmptyValues as $key => $value) {
            // Check if key starts with '#' - if so, output the key (without #) followed by value
            if (is_string($key) && str_starts_with($key, '#')) {
                $formattedKey = substr($key, 1); // Remove '#' from the beginning
                if (!empty($formattedValues)) {
                    $formattedValues[] = ', ' . $formattedKey . ' ' . $value;
                } else {
                    $formattedValues[] = $formattedKey . ' ' . $value;
                }

            }
            // Original logic for handling normal values
            else if (!empty($formattedValues) && !($key === 'date' && $previousKey === 'place')) {
                $formattedValues[] = ', ' . $value;
            } else {
                if (!empty($formattedValues) && $key === 'date' && $previousKey === 'place') {
                    $formattedValues[] = ' ' . $value; // Space instead of comma
                } else {
                    $formattedValues[] = $value;
                }
            }

            $previousKey = $key;
        }



        $html .= '<div>';
        $html .= '<dt>'.$label.'</dt>';
        $html .= '<dd>'.implode('', $formattedValues).'</dd>';
        $html .= '</div>';

        return $html;
    }

}
