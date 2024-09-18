<?php
defined('TYPO3') or die('Access denied.');
call_user_func(function()
{

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
        'liszt_common',
        'Configuration/TsConfig/page.tsconfig',
        'Liszt-Common'
    );
});
