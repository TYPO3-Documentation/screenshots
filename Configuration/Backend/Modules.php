<?php

return [
    'tools_Screenshots' => [
        'parent' => 'tools',
        'position' => [], // 'after' => 'web_info'
        'access' => 'admin',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-screenshots',
        'path' => '/module/tools/ScreenshotsManager',
        'labels' => 'Screenshots', // 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Screenshots',
        'controllerActions' => [
            ScreenshotsManagerController::class => [
                'index ',
                'make',
                'compare'
            ],
        ],
    ],
];

/*
if (\TYPO3\CMS\Core\Core\Environment::getContext()->isTesting() === false) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Screenshots',
        'tools',
        'screenshots',
        '',
        [
            \TYPO3\Documentation\Screenshots\Controller\ScreenshotsManagerController::class => 'index, make, compare',
        ],
        [
            'access' => 'user,group',
            'icon'   => 'EXT:screenshots/Resources/Public/Icons/module-screenshots.svg',
            'labels' => 'LLL:EXT:screenshots/Resources/Private/Language/locallang_mod.xlf',
        ]
    );
}
*/
