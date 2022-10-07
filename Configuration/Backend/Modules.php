<?php

use TYPO3\Documentation\Screenshots\Controller\ScreenshotsManagerController;

return [
    'tools_screenshots' => [
        'parent' => 'tools',
        'position' => 'bottom',
        'access' => 'user,group',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-screenshots',
        'path' => '/module/tools/screenshots',
        'labels' => 'LLL:EXT:screenshots/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Screenshots',
        'controllerActions' => [
            ScreenshotsManagerController::class => [
                'index',
                'make',
                'compare'
            ],
        ],
    ],
];
