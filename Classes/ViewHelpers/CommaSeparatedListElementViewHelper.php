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
        foreach ($nonEmptyValues as $key => $value) {
            if (is_string($key) && !is_numeric($key)) {
                $formattedValues[] = $key . ' ' . $value;
            } else {
                $formattedValues[] = $value;
            }
        }

        $html .= '<div>';
        $html .= '<dt>'.$label.'</dt>';
        $html .= '<dd>'.implode(', ', $formattedValues).'</dd>';
        $html .= '</div>';

        return $html;
    }

}
