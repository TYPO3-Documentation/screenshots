<?php

declare(strict_types=1);
namespace TYPO3\Documentation\Screenshots\Controller;

/*
 * This file is part of the TYPO3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

# use Psr\Log\LoggerAwareInterface;
# use Psr\Log\LoggerAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;
use TYPO3\Documentation\Screenshots\Comparison\File;
use TYPO3\Documentation\Screenshots\Comparison\ImageComparison;
use TYPO3\Documentation\Screenshots\Comparison\TextFileComparison;
use TYPO3\Documentation\Screenshots\Configuration\ConfigurationRepository;
use TYPO3\Documentation\Screenshots\Configuration\ExtensionConfiguration;
use TYPO3\Documentation\Screenshots\Util\FileHelper;

class ScreenshotsManagerController extends ActionController
{
    protected float $threshold = 0.0002;
    protected array $imageExtensions = ['gif', 'jpg', 'jpeg', 'png', 'bmp'];

    protected PageRenderer $pageRenderer;

    public function injectPageRenderer(PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * Function will be called before every other action
     */
    protected function initializeAction()
    {
        $this->pageUid = (int)($this->request->getQueryParams()['id'] ?? 0);
        // $this->screenshotsConfig = $this->extensionConfiguration->get('screenshots') ?? [];
        parent::initializeAction();
    }

    protected function initializeView($view)
    {
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:screenshots/Resources/Private/Language/locallang_mod.xlf');
        $this->pageRenderer->addCssFile('EXT:screenshots/Resources/Public/Css/screenshots-manager.css');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Screenshots/ScreenshotsManager');
    }

    /**
     * Generates the action menu
     */
    protected function initializeModuleTemplate(ServerRequestInterface $request): ModuleTemplate
    {
        $menuItems = [
            'index' => [
                'controller' => 'ScreenshotsManager',
                'action' => 'index',
                'label' => $this->getLanguageService()->sL('LLL:EXT:screenshots/Resources/Private/Language/locallang_mod.xlf:module.menu.index'),
            ],
            'make' => [
                'controller' => 'ScreenshotsManager',
                'action' => 'make',
                'label' => $this->getLanguageService()->sL('LLL:EXT:screenshots/Resources/Private/Language/locallang_mod.xlf:module.menu.make'),
            ],
            'compare' => [
                'controller' => 'ScreenshotsManager',
                'action' => 'compare',
                'label' => $this->getLanguageService()->sL('LLL:EXT:screenshots/Resources/Private/Language/locallang_mod.xlf:module.menu.compare'),
            ],
        ];
        $view = $this->moduleTemplateFactory->create($request);

        $menu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('ScreenshotsModuleMenu');

        $context = '';
        foreach ($menuItems as $menuItemConfig) {
            $isActive = $this->request->getControllerActionName() === $menuItemConfig['action'];
            $menuItem = $menu->makeMenuItem()
                ->setTitle($menuItemConfig['label'])
                ->setHref($this->uriBuilder->reset()->uriFor($menuItemConfig['action'], [], $menuItemConfig['controller']))
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
            if ($isActive) {
                $context = $menuItemConfig['label'];
            }
        }

        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);

        $view->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:screenshots/Resources/Private/Language/locallang_mod.xlf:module.tabs.tab'),
            $context
        );

        $permissionClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRecord = BackendUtility::readPageAccess($this->pageUid, $permissionClause);
        if ($pageRecord) {
            $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        }
        $view->setFlashMessageQueue($this->getFlashMessageQueue());

        return $view;
    }

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly IconFactory $iconFactory
    ) {
    }

    public function indexAction(): ResponseInterface
    {
        #var_dump($this->getExtensionConfiguration());
        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple([
            'pathOriginal' => $this->getExtensionConfiguration()->getOriginalPath(),
            'pathActual' => $this->getExtensionConfiguration()->getActualPath()
        ]);
        return $view->renderResponse();
    }

    public function makeAction(
        string $cmd = 'show',
        string $pathFilter = '',
        string $suiteIdFilter = '',
        string $actionsIdFilter = ''
    ): ResponseInterface
    {
        if ($cmd === 'make') {
            $this->make($pathFilter, $suiteIdFilter, $actionsIdFilter);
        }

        $originalPath = $this->getExtensionConfiguration()->getAbsoluteOriginalPath();
        $configurationRepository = $this->getConfigurationRepository($originalPath);
        $configurations = $configurationRepository->findAll();

        $actionsIds = [];
        foreach ($configurations as &$configuration) {
            $configuration->read();
            foreach ($configuration->getSelectableActionsIds() as $actionsId) {
                if (!isset($actionsIds[$actionsId])) {
                    $actionsIds[$actionsId] = $actionsId;
                }
            }
        }

        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple([
            'cmd' => $cmd,
            'pathFilter', $pathFilter,
            'suiteIdFilter' => $suiteIdFilter,
            'actionsIdFilter' => $actionsIdFilter,
            'configurations' => $configurations,
            'actionsIds' => $actionsIds,
            'messages' => $this->fetchMessages()
        ]);
        return $view->renderResponse();
    }

    protected function make(string $pathFilter = '', string $suiteIdFilter = '', string $actionsIdFilter = ''): void
    {
        $resultCode = 0;

        $output = $this->executeCommandAndFetchOutput(
            sprintf(
                '/var/www/html/.ddev/commands/web/make-screenshots ' .
                '--target-path=%s ' .
                '--suite-id=%s ' .
                '--actions-id=%s',
                $pathFilter, $suiteIdFilter, $actionsIdFilter
            ),
            $resultCode
        );

        $converter = new AnsiToHtmlConverter();
        $outputHtml = $converter->convert($output);

        if ($resultCode === 0) {
            $link = sprintf('<a href="%s">next</a>', $this->uriBuilder->reset()->uriFor('compare'));
            $message = sprintf(
                'Successfully created screenshots. You will probably compare them with the original ones %s.',
                $link
            );
            $this->pushMessage($message, InfoboxViewHelper::STATE_OK);
        } else {
            $message = 'Creating screenshots has failed. Please find the details below.';
            $this->pushMessage($message, InfoboxViewHelper::STATE_ERROR);
        }

        $this->view->assign('outputHtml', $outputHtml);
    }

    protected function executeCommandAndFetchOutput(string $command, int &$lastResultCode): string
    {
        if ($lastResultCode === 0) {
            $output = sprintf('$ %s', $command) . "\n";
            exec($command . " 2>&1", $outputArray, $lastResultCode);
            $output .= implode("\n", $outputArray);
        } else {
            $output = sprintf('$ %s', $command) . "\n";
            $output .= "=> skip" . "\n";
        }
        return $output;
    }

    public function compareAction(
        string $cmd = 'show',
        string $search = '',
        string $sorting = 'difference-desc',
        array $imagesToCopy = [],
        array $textFilesToCopy = [],
        int $numImages = 0,
        int $numTextFiles = 0
    ): ResponseInterface
    {
        if ($cmd === 'compare') {
            $this->compare($search, $sorting);
        }
        elseif ($cmd === 'copy') {
            $this->copy($imagesToCopy, $textFilesToCopy, $numImages, $numTextFiles);
            $this->compare($search, $sorting);
        }

        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple([
            'cmd' => $cmd,
            'messages' => $this->fetchMessages()
        ]);
        return $view->renderResponse();
    }

    protected function compare(string $search, string $sorting): void
    {
        $pathOriginal = $this->getExtensionConfiguration()->getAbsoluteOriginalPath();
        $pathActual = $this->getExtensionConfiguration()->getAbsoluteActualPath();
        $pathDiff = $this->getExtensionConfiguration()->getAbsoluteDiffPath();
        $urlOriginal = $this->getExtensionConfiguration()->getAbsoluteOriginalUrl();
        $urlActual = $this->getExtensionConfiguration()->getAbsoluteActualUrl();
        $urlDiff = $this->getExtensionConfiguration()->getAbsoluteDiffUrl();

        FileHelper::deleteRecursively($pathDiff);

        $files = GeneralUtility::removePrefixPathFromList(GeneralUtility::getAllFilesAndFoldersInPath(
            [], $pathActual . '/'
        ), $pathActual);

        $imageComparisons = [];
        $textFileComparisons = [];
        $imageExtensionsIndex = array_flip($this->imageExtensions);
        $numImagesFilteredOut = 0;
        $numTextFilesFilteredOut = 0;
        foreach ($files as $file) {
            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

            if (isset($imageExtensionsIndex[$fileExtension])) {
                if ($this->isSearchTermMatchingFilePath($search, $file)) {
                    $imageComparison = new ImageComparison(
                        $pathActual . $file,
                        $pathOriginal . $file,
                        $pathDiff . $file,
                        $urlActual . $file,
                        $urlOriginal . $file,
                        $urlDiff . $file,
                        $this->threshold
                    );
                    $imageComparison->process();
                    if ($imageComparison->getDifference() > $this->threshold) {
                        $imageComparisons[] = $imageComparison;
                    }
                } else {
                    $numImagesFilteredOut++;
                }
            } else {
                if ($this->isSearchTermMatchingFilePath($search, $file)) {
                    $textFileComparison = new TextFileComparison(
                        $pathActual . $file,
                        $pathOriginal . $file,
                        $pathDiff . $file,
                        $urlActual . $file,
                        $urlOriginal . $file,
                        $urlDiff . $file
                    );
                    $textFileComparison->process();
                    if ($textFileComparison->getDifference() > 0) {
                        $textFileComparisons[] = $textFileComparison;
                    }
                } else {
                    $numTextFilesFilteredOut++;
                }
            }
        }

        $this->sortImageComparisons($imageComparisons, $sorting);
        $this->sortTextFileComparisons($textFileComparisons, $sorting);

        $this->view->assign('imageComparisons', $imageComparisons);
        $this->view->assign('textFileComparisons', $textFileComparisons);
        $this->view->assign('numImagesFilteredOut', $numImagesFilteredOut);
        $this->view->assign('numTextFilesFilteredOut', $numTextFilesFilteredOut);
        $this->view->assign('search', $search);
        $this->view->assign('sorting', $sorting);
    }

    protected function isSearchTermMatchingFilePath(string $search, string $filePath): bool
    {
        if (empty($search)) {
            return true;
        }

        $isSearchByRegexp = strpos($search, '#') === 0;
        if ($isSearchByRegexp) {
            return preg_match($search, $filePath) === 1;
        } else {
            return strpos($filePath, $search) !== false;
        }
    }

    protected function sortImageComparisons(array &$imageComparisons, string $sorting): void
    {
        if ($sorting === 'difference-asc') {
            usort(
                $imageComparisons,
                function (ImageComparison $imageComparisonA, ImageComparison $imageComparisonB) {
                    return $imageComparisonA->getDifference() <= $imageComparisonB->getDifference() ? -1 : 1;
                }
            );
        } elseif ($sorting === 'difference-desc') {
            usort(
                $imageComparisons,
                function(ImageComparison $imageComparisonA, ImageComparison $imageComparisonB) {
                    return $imageComparisonA->getDifference() <= $imageComparisonB->getDifference() ? 1 : -1;
                }
            );
        } elseif ($sorting === 'filename-asc') {
            usort(
                $imageComparisons,
                function(ImageComparison $imageComparisonA, ImageComparison $imageComparisonB) {
                    return strcasecmp(
                        $imageComparisonA->getImageOriginal()->getFileName(),
                        $imageComparisonB->getImageOriginal()->getFileName()
                    );
                }
            );
        } elseif ($sorting === 'filename-desc') {
            usort(
                $imageComparisons,
                function(ImageComparison $imageComparisonA, ImageComparison $imageComparisonB) {
                    return strcasecmp(
                        $imageComparisonA->getImageOriginal()->getFileName(),
                        $imageComparisonB->getImageOriginal()->getFileName()
                    ) * -1;
                }
            );
        } elseif ($sorting === 'path-asc') {
            usort(
                $imageComparisons,
                function(ImageComparison $imageComparisonA, ImageComparison $imageComparisonB) {
                    return strcasecmp(
                        $imageComparisonA->getImageOriginal()->getPath(),
                        $imageComparisonB->getImageOriginal()->getPath()
                    );
                }
            );
        } elseif ($sorting === 'path-desc') {
            usort(
                $imageComparisons,
                function(ImageComparison $imageComparisonA, ImageComparison $imageComparisonB) {
                    return strcasecmp(
                        $imageComparisonA->getImageOriginal()->getPath(),
                        $imageComparisonB->getImageOriginal()->getPath()
                    ) * -1;
                }
            );
        }
    }

    protected function sortTextFileComparisons(array &$textFileComparisons, string $sorting): void
    {
        if ($sorting === 'difference-asc') {
            usort(
                $textFileComparisons,
                function (TextFileComparison $textFileComparisonA, TextFileComparison $textFileComparisonB) {
                    return $textFileComparisonA->getDifference() <= $textFileComparisonB->getDifference() ? -1 : 1;
                }
            );
        } elseif ($sorting === 'difference-desc') {
            usort(
                $textFileComparisons,
                function (TextFileComparison $textFileComparisonA, TextFileComparison $textFileComparisonB) {
                    return $textFileComparisonA->getDifference() <= $textFileComparisonB->getDifference() ? 1 : -1;
                }
            );
        } elseif ($sorting === 'filename-asc') {
            usort(
                $textFileComparisons,
                function(TextFileComparison $textFileComparisonA, TextFileComparison $textFileComparisonB) {
                    return strcasecmp(
                        $textFileComparisonA->getFileOriginal()->getFileName(),
                        $textFileComparisonB->getFileOriginal()->getFileName()
                    );
                }
            );
        } elseif ($sorting === 'filename-desc') {
            usort(
                $textFileComparisons,
                function(TextFileComparison $textFileComparisonA, TextFileComparison $textFileComparisonB) {
                    return strcasecmp(
                            $textFileComparisonA->getFileOriginal()->getFileName(),
                            $textFileComparisonB->getFileOriginal()->getFileName()
                        ) * -1;
                }
            );
        } elseif ($sorting === 'path-asc') {
            usort(
                $textFileComparisons,
                function(TextFileComparison $textFileComparisonA, TextFileComparison $textFileComparisonB) {
                    return strcasecmp(
                        $textFileComparisonA->getFileOriginal()->getPath(),
                        $textFileComparisonB->getFileOriginal()->getPath()
                    );
                }
            );
        } elseif ($sorting === 'path-desc') {
            usort(
                $textFileComparisons,
                function(TextFileComparison $textFileComparisonA, TextFileComparison $textFileComparisonB) {
                    return strcasecmp(
                            $textFileComparisonA->getFileOriginal()->getPath(),
                            $textFileComparisonB->getFileOriginal()->getPath()
                        ) * -1;
                }
            );
        }
    }

    protected function copy(array $imagesToCopy, array $textFilesToCopy, int $numImages, int $numTextFiles): void
    {
        $pathOriginal = $this->getExtensionConfiguration()->getAbsoluteOriginalPath();
        $pathActual = $this->getExtensionConfiguration()->getAbsoluteActualPath();
        $urlActual = $this->getExtensionConfiguration()->getAbsoluteActualUrl();

        $files = GeneralUtility::removePrefixPathFromList(GeneralUtility::getAllFilesAndFoldersInPath(
            [], $pathActual . '/'
        ), $pathActual);

        $imageExtensionsIndex = array_flip($this->imageExtensions);
        $imagesToCopyIndex = array_flip($imagesToCopy);
        $textFilesToCopyIndex = array_flip($textFilesToCopy);

        $numCopiedImages = 0;
        $numCopiedFiles = 0;
        foreach ($files as $file) {
            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

            if (isset($imageExtensionsIndex[$fileExtension])) {
                $image = new File($pathActual . $file, $urlActual . $file);
                if (isset($imagesToCopyIndex[$image->getHash()])) {
                    $image->copy($pathOriginal . $file);
                    $numCopiedImages++;
                }
            } else {
                $textFile = new File($pathActual . $file, $urlActual . $file);
                if (isset($textFilesToCopyIndex[$textFile->getHash()])) {
                    $textFile->copy($pathOriginal . $file);
                    $numCopiedFiles++;
                }
            }
        }

        if ($numImages === 1) {
            $message = sprintf('%d of %d image ', $numCopiedImages, $numImages);
        } else {
            $message = sprintf('%d of %d images ', $numCopiedImages, $numImages);
        }
        if ($numCopiedFiles === 1) {
            $message .= sprintf('and %d of %d text file copied.', $numCopiedFiles, $numTextFiles);
        } else {
            $message .= sprintf('and %d of %d text files copied.', $numCopiedFiles, $numTextFiles);
        }
        $this->pushMessage($message, InfoboxViewHelper::STATE_OK);
    }

    /**
     * Add a message to the module internal message stack. They are displayed as infoboxes at a user-defined
     * position in the template. Survives redirects similar to $this->addFlashMessage().
     *
     * @param string $message
     * @param int $state An InfoboxViewHelper state.
     */
    protected function pushMessage(string $message, int $state): void
    {
        $moduleData = $this->getBackendUser()->getModuleData('tx_screenshots');
        $moduleData['messages'][] = ['message' => $message, 'state' => $state];
        $this->getBackendUser()->pushModuleData('tx_screenshots', $moduleData);
    }

    /**
     * Retrieve messages from the module internal message stack.
     *
     * @return array
     */
    protected function fetchMessages(): array
    {
        $messages = [];

        $moduleData = $this->getBackendUser()->getModuleData('tx_screenshots');
        if (isset($moduleData['messages'])) {
            $messages = $moduleData['messages'];
            unset($moduleData['messages']);
            $this->getBackendUser()->pushModuleData('tx_screenshots', $moduleData);
        }

        return $messages;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getExtensionConfiguration(): ExtensionConfiguration
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }

    protected function getConfigurationRepository(string $basePath): ConfigurationRepository
    {
        return GeneralUtility::makeInstance(ConfigurationRepository::class, $basePath);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
