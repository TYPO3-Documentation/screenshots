<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'screenshots',
    'description' => 'Provide tools and runner to take scripted screenshots of the TYPO3 CMS.',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'TYPO3\\Documentation\\Screenshots\\' => 'Classes/',
        ],
    ],
];
