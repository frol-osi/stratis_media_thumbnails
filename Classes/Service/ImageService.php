<?php
declare(strict_types=1);

namespace Stratis\StratisMediaThumbnails\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;

/**
 * Class ImageService
 * @package Stratis\StratisMediaThumbnails\Service
 */
class ImageService
{
    const DEFAULT_FILE_EXTENSION = 'jpg';

    /**
     * @param string $originalFileName
     * @param array $configuration
     * @return array|bool|null
     */
    public static function cropScale(string $originalFileName, array $configuration)
    {
        $result = null;
        $gifBuilder = GeneralUtility::makeInstance(GifBuilder::class);

        if (empty($configuration['fileExtension'])) {
            $configuration['fileExtension'] = self::DEFAULT_FILE_EXTENSION;
        }

        $options = self::getConfigurationForImageCropScaleMask($configuration, $gifBuilder);

        $croppedImage = null;
        if (!empty($configuration['crop'])) {

            // check if it is a json object
            $cropData = json_decode($configuration['crop']);
            if ($cropData) {
                $crop = implode(',', [(int)$cropData->x, (int)$cropData->y, (int)$cropData->width, (int)$cropData->height]);
            } else {
                $crop = $configuration['crop'];
            }

            list($offsetLeft, $offsetTop, $newWidth, $newHeight) = explode(',', $crop, 4);

            $backupPrefix = $gifBuilder->filenamePrefix;
            $gifBuilder->filenamePrefix = 'crop_';

            $jpegQuality = MathUtility::forceIntegerInRange($GLOBALS['TYPO3_CONF_VARS']['GFX']['jpg_quality'], 10, 100, 85);

            // the result info is an array with 0=width,1=height,2=extension,3=filename
            $result = $gifBuilder->imageMagickConvert(
                $originalFileName,
                $configuration['fileExtension'],
                '',
                '',
                sprintf('-crop %dx%d+%d+%d +repage -quality %d', $newWidth, $newHeight, $offsetLeft, $offsetTop, $jpegQuality),
                '',
                ['noScale' => true],
                true
            );
            $gifBuilder->filenamePrefix = $backupPrefix;

            if ($result !== null) {
                $originalFileName = $croppedImage = $result[3];
            }
        }

        $result = $gifBuilder->imageMagickConvert(
            $originalFileName,
            $configuration['fileExtension'],
            $configuration['width'],
            $configuration['height'],
            $configuration['additionalParameters'],
            $configuration['frame'],
            $options
        );

        // check if the processing really generated a new file (scaled and/or cropped)
        if ($result !== null) {
            if ($result[3] !== $originalFileName || $originalFileName === $croppedImage) {
                $result = [
                    'width' => $result[0],
                    'height' => $result[1],
                    'filePath' => $result[3],
                ];
            } else {
                // No file was generated
                $result = null;
            }
        }

        // Cleanup temp file if it isn't used as result
        if ($croppedImage && ($result === null || $croppedImage !== $result['filePath'])) {
            GeneralUtility::unlink_tempfile($croppedImage);
        }

        if ($result !== null && !empty($configuration['override']) && @file_exists($result['filePath'])) {
            unlink($originalFileName);
            rename($result['filePath'], $originalFileName);
        }

        return $result;
    }

    /**
     * @param array $configuration
     * @param GifBuilder $gifBuilder
     * @return array
     */
    protected static function getConfigurationForImageCropScaleMask(array $configuration, \TYPO3\CMS\Frontend\Imaging\GifBuilder $gifBuilder)
    {
        if ($configuration['useSample']) {
            $gifBuilder->scalecmd = '-sample';
        }
        $options = [];
        if ($configuration['maxWidth']) {
            $options['maxW'] = $configuration['maxWidth'];
        }
        if ($configuration['maxHeight']) {
            $options['maxH'] = $configuration['maxHeight'];
        }
        if ($configuration['minWidth']) {
            $options['minW'] = $configuration['minWidth'];
        }
        if ($configuration['minHeight']) {
            $options['minH'] = $configuration['minHeight'];
        }

        $options['noScale'] = $configuration['noScale'];

        return $options;
    }
}
