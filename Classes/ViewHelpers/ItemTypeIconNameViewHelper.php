<?php
declare(strict_types=1);

namespace Slub\LisztCommon\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use Quellenform\Iconpack\IconpackFactory;
use Quellenform\Iconpack\IconpackRegistry;


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
        $iconpackRegistry = GeneralUtility::makeInstance(IconpackRegistry::class);
        $iconPackKey = $arguments['iconPackKey'];

        try {
            $iconpackProvider = $iconpackRegistry->getIconpackProviderByIdentifier($iconPackKey);
            if ($iconpackProvider) {
                $availableIconsArray = $iconpackProvider->getIcons();
                $itemType = $arguments['itemType'];

                if (is_array($availableIconsArray) && array_key_exists($itemType, $availableIconsArray)) {
                    return $iconPackKey . ',' . $itemType;
                }
            }
        } catch (\Exception $e) {
            // Iconpack not found, return default
        }

        return 'lziconsr,lisztDocument';
    }
}
