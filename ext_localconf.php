<?php
defined('TYPO3_MODE') || die;

call_user_func(function () {
    \Stratis\StratisMediaThumbnails\Service\ExtensionConfigurationService::updateMediafileExt();


    /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class,
        \TYPO3\CMS\Core\Resource\Service\FileProcessingService::SIGNAL_PreFileProcess,
        \Stratis\StratisMediaThumbnails\Processor\LocalMediaProcessor::class,
        'generate'
    );
});



