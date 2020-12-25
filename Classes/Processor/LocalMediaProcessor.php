<?php
declare(strict_types=1);

namespace Stratis\StratisMediaThumbnails\Processor;

use Stratis\StratisMediaThumbnails\Service\ExtensionConfigurationService;
use Stratis\StratisMediaThumbnails\Service\ImageService;
use Stratis\StratisMediaThumbnails\Service\LocalMediaThumbnailsService;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\Service\FileProcessingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LocalMediaProcessor extends LocalImageProcessor
{
    /**
     * @var string[]
     */
    protected $allowedExtensions = [];

    /**
     * LocalMediaProcessor constructor.
     */
    public function __construct()
    {
        $this->allowedExtensions = ExtensionConfigurationService::getAllowedExtensions();
    }

    /**
     * @param FileProcessingService $service
     * @param DriverInterface $driver
     * @param ProcessedFile $processedFile
     * @param FileInterface $file
     * @param $context
     * @param array $configuration
     */
    public function generate(FileProcessingService $service, DriverInterface $driver, ProcessedFile $processedFile, FileInterface $file, $context, array $configuration = [])
    {
        if (!$processedFile->isProcessed()) {
            if (in_array($file->getExtension(), $this->allowedExtensions)) {
                $this->process($processedFile);
            }
        }
    }

    /**
     * Processes the file
     *
     * @param ProcessedFile $processedFile
     */
    protected function process(ProcessedFile $processedFile)
    {
        if ($processedFile->isNew() || (!$processedFile->usesOriginalFile() && !$processedFile->exists()) ||
            $processedFile->isOutdated()) {

            $task = $processedFile->getTask();
            $this->processTask($task);

            if ($task->isExecuted() && $task->isSuccessful() && $processedFile->isProcessed()) {
                /** @var ProcessedFileRepository $processedFileRepository */
                $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
                $processedFileRepository->add($processedFile);
            }
        }
    }

    /**
     * Processes the given task.
     *
     * @param TaskInterface $task
     * @throws \InvalidArgumentException
     */
    public function processTask(TaskInterface $task)
    {
        if (!$this->canProcessTask($task)) {
            throw new \InvalidArgumentException('Cannot process task of type "' . $task->getType() . '.' . $task->getName() . '"', 1350570621);
        }

        if ($this->checkForExistingTargetFile($task)) {
            return;
        }

        $originalFileName = $task->getSourceFile()->getForLocalProcessing(false);
        $targetFilePath = GeneralUtility::tempnam('preview_', '.' . $task->getTargetFileExtension());
        try {
            if (LocalMediaThumbnailsService::getPreview($originalFileName, $targetFilePath) !== null) {
                $configuration = $task->getTargetFile()->getProcessingConfiguration();

                //Scale for preview
                $configuration['override'] = true;
                ImageService::cropScale($targetFilePath, $configuration);

                $imageDimensions = GeneralUtility::makeInstance(GraphicalFunctions::class)->getImageDimensions($targetFilePath);
                $task->getTargetFile()->setName($task->getTargetFileName());
                $task->getTargetFile()->updateProperties([
                    'width' => $imageDimensions[0],
                    'height' => $imageDimensions[1],
                    'size' => filesize($targetFilePath),
                    'checksum' => $task->getConfigurationChecksum()
                ]);
                $task->getTargetFile()->updateWithLocalFile($targetFilePath);
                $task->setExecuted(true);
            }
        } catch (\Exception $e) {
            $task->setExecuted(false);
        }
    }
}
