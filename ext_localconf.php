<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

ExtensionManagementUtility::addTypoScriptConstants(
    'plugin.tx_nxpdfrendering.configuration.pdfRenderingPageType = ' .
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxpdfrendering']['pdfRenderingPageType'],
);
ExtensionManagementUtility::addTypoScriptConstants(
    'plugin.tx_nxpdfrendering.configuration.printRenderingPageType = ' .
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxpdfrendering']['printRenderingPageType'],
);
