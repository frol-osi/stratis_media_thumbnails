<?php
declare(strict_types=1);

namespace Stratis\StratisMediaThumbnails\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExtensionConfigurationService
 * @package Stratis\StratisMediaThumbnails\Service
 */
class ExtensionConfigurationService
{
    /**
     * @return array
     */
    public static function getAllowedExtensions(): array
    {
        $allowedExtensions = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('stratis_media_thumbnails', 'extensions');

        return GeneralUtility::trimExplode(',', $allowedExtensions, true);
    }

    public static function updateMediafileExt()
    {
        $allowed = self::getAllowedExtensions();

        $enabled = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'], true);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] = implode(',', array_unique(array_merge($allowed, $enabled)));
    }
}
