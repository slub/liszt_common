<?php
declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use Quellenform\Iconpack\IconpackFactory;


final class ItemTypeIconNameViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('iconPackKey', 'string', 'Name of the key for the IconPack (from Iconpack.yaml)', true);
        $this->registerArgument('itemType', 'string', 'Name of the itemType (from Zotero)', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext)
    : ?string
    {
        // get installed icon names from the t3x Iconpack Extension with Key 'lziconsr' as Array
        $iconpackFactory = GeneralUtility::makeInstance(IconpackFactory::class);
        $iconPackKey = $arguments['iconPackKey'];
        $availableIconsArray =  $iconpackFactory->queryConfig($iconPackKey, 'icons');

        // Check if itemType exists as a key in the array
        $itemType = $arguments['itemType'];
        if (array_key_exists($itemType, $availableIconsArray)) {
            return $iconPackKey.','.$itemType;
        }

        // else Return default icon
        return 'lziconsr,lisztDocument';

    }
}
