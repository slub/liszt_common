<?php

defined('TYPO3') or die();

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'liszt_common',
        'Configuration/TypoScript',
        'Liszt-Common'
);
