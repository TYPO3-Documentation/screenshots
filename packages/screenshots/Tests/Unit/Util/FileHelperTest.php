<?php

declare(strict_types=1);
namespace TYPO3\CMS\Screenshots\Tests\Unit\Util;

/*
 * This file is part of the TYPO3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Screenshots\Util\FileHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FileHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getFoldersRecursively(): void
    {
        $folderTree = [
            'FolderA' => [],
            'FolderB' => [
                'SubFolderA' => []
            ],
        ];
        $expected = [
            'vfs://t3docs/FolderA',
            'vfs://t3docs/FolderB',
            'vfs://t3docs/FolderB/SubFolderA',
        ];

        $root = vfsStream::setup('t3docs', null, $folderTree);
        $actual = FileHelper::getFoldersRecursively($root->url());

        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getSubFolders(): void
    {
        $folderTree = [
            'FolderA' => [],
            'FolderB' => [
                'SubFolderA' => []
            ],
        ];
        $expected = [
            'vfs://t3docs/FolderA',
            'vfs://t3docs/FolderB',
        ];

        $root = vfsStream::setup('t3docs', null, $folderTree);
        $actual = FileHelper::getSubFolders($root->url());

        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function deleteRecursively(): void
    {
        $folderTree = [
            'FolderA' => [],
            'FolderB' => [
                'SubFolderA' => [
                    'fileA.txt' => 'fileContentA',
                    'fileB.txt' => 'fileContentB',
                ]
            ],
        ];

        $root = vfsStream::setup('t3docs', null, $folderTree);
        self::assertDirectoryExists($root->url());
        FileHelper::deleteRecursively($root->url());
        self::assertDirectoryDoesNotExist($root->url());
    }
}
