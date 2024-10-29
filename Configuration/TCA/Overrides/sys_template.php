<?php
defined('TYPO3') or die('Access denied.');
call_user_func(function()
{

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'liszt_common',
        'Configuration/TypoScript',
        'Liszt-Common'
    );
});
