<?php

namespace Slub\LisztCommon\Event\Listener;

use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationLoadedEvent;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Event to modify the site configuration array before loading the configuration
 */
final class SiteConfigurationLoadedEventListener
{

    public function modify(SiteConfigurationLoadedEvent $event): void
    {
        $configuration = $event->getConfiguration();
        $fileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $routeEnhancersConfiguration = $fileLoader->load('EXT:liszt_common/Configuration/Routing/routeEnhancers.yaml');
        ArrayUtility::mergeRecursiveWithOverrule($configuration, $routeEnhancersConfiguration);
        $event->setConfiguration($configuration);
    }

}
