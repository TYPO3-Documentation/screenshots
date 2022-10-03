<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Screenshots',
    'description' => 'Provide tools and runner to take scripted screenshots of the TYPO3 CMS.',
    'category' => 'plugin',
    'author' => 'TYPO3 Documentation Team',
    'author_email' => 'documentation@typo3.org',
    'state' => 'stable',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '11.1.2',
    'autoload' => [
        'psr-4' => [
            "TYPO3\\Documentation\\Screenshots\\" => "Classes/",
            "TYPO3\\Documentation\\Screenshots\\Tests\\" => "Tests/"
        ]
    ],
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-11.99.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            "friendsoftypo3/extension-builder" => "11.0.0-11.99.99",
            "georgringer/news" => "9.0.0-10.99.99",
            "t3docs/site-package" => "1.0.0-1.99.99",
            "t3docs/examples" => "11.0.0-11.99.99",
            "typo3/cms-introduction" => "4.4.1-4.99.99",
            "typo3/cms-styleguide" => "11.0.3 - 11.99.99",
            "typo3/cms-workspaces" => "11.0.0-11.99.99"
        ],
    ],
];
