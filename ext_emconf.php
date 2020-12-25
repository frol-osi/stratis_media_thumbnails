<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Stratis Media Thumbnails',
    'description' => 'Helper to allow get preview from media files',
    'category' => 'misc',
    'author_email' => 'frolfrolenko@o-s-i.org',
    'author_company' => 'Stratis',
    'state' => 'beta',
    'uploadFolder' => false,
    'clearCacheOnLoad' => true,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.9.99',
        ],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Stratis\\StratisMediaThumbnails\\' => 'Classes/',
        ],
    ],
];
