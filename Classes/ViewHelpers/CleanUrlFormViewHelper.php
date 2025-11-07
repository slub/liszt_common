<?php
declare(strict_types=1);
/**
 * This class extends the TYPO3 CMS Fluid FormViewHelper.
 * It overrides the `renderHiddenReferrerFields` method to return an empty string instead of __referrer and __trustedProperties fields in GET URL
 * Attention: you now have to take care of checking the search parameters and protection against CSRF attacks yourself!
 */

namespace Slub\LisztCommon\ViewHelpers;



class CleanUrlFormViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper {
    protected function renderHiddenReferrerFields(): string
    {
        return '';
    }
    protected function renderTrustedPropertiesField(): string
    {
        return '';
    }
}
