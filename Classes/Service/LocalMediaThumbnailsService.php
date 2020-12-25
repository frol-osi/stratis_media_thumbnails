<?php
declare(strict_types=1);

namespace Stratis\StratisMediaThumbnails\Service;

use FFMpeg;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;

/**
 * Class LocalMediaThumbnailsService
 * @package Stratis\StratisMediaThumbnails\Service
 */
class LocalMediaThumbnailsService extends LocalImageProcessor
{
    /**
     * @param string $mediaFilePath
     * @param string $previewFilePath
     * @param int $
     * @return string|null
     */
    public static function getPreview(string $mediaFilePath, string $previewFilePath, int $fromSeconds = 0)
    {
        try {
            $ffmpeg = FFMpeg\FFMpeg::create();
            $video = $ffmpeg->open($mediaFilePath);
            $video
                ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($fromSeconds))
                ->save($previewFilePath);

        } catch (\Exception $e) {
            return null;
        }

        return $previewFilePath;
    }
}
