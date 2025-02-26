<?php

namespace Slub\LisztCommon\Event\Listener;

use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationLoadedEvent;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;


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

        // get detailPageId from extension configuration and write this value as limitToPages in routeEnhancer
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('liszt_common');
        $detailPageId = $extConf['detailPageId'];
        if (
            isset($routeEnhancersConfiguration['routeEnhancers']['SearchDetailsRoute']) &&
            is_array($routeEnhancersConfiguration['routeEnhancers']['SearchDetailsRoute'])
        ) {
            $routeEnhancersConfiguration['routeEnhancers']['SearchDetailsRoute']['limitToPages'] = [$detailPageId];
        }


        ArrayUtility::mergeRecursiveWithOverrule($configuration, $routeEnhancersConfiguration);
        $event->setConfiguration($configuration);
    }

}
