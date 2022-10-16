<?php

declare(strict_types=1);
namespace TYPO3\Documentation\Screenshots\Core\Acceptance\Extension;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Codeception\Event\SuiteEvent;
use TYPO3\Documentation\Screenshots\Core\Functional\Framework\DataHandling\DataSet;
use TYPO3\Documentation\Screenshots\Core\Testbase;

/**
 * This codeception extension creates a full TYPO3 instance within
 * typo3temp. Own acceptance test suites should extend from this class
 * and change the properties. This can be used to not copy the whole
 * bootstrapTypo3Environment() method but reuse it instead.
 */
abstract class BackendEnvironment extends AbstractBackendSuiteEnvironment
{
    /**
     * Handle SUITE_BEFORE event.
     *
     * Create a full standalone TYPO3 instance within typo3temp/var/tests/acceptance,
     * create a database and create database schema.
     *
     * @param SuiteEvent $suiteEvent
     */
    public function bootstrapTypo3Environment(SuiteEvent $suiteEvent): void
    {
        if (!$this->config['typo3Setup']) {
            return;
        }
        $testbase = new Testbase();
        $testbase->defineOriginalWebRootPath();
        $testbase->defineOriginalAppRootPath();
        $testbase->createDirectory(ORIGINAL_WEB_ROOT . 'typo3temp/var/tests/acceptance');
        $testbase->createDirectory(ORIGINAL_WEB_ROOT . 'typo3temp/var/transient');

        $instancePath = ORIGINAL_WEB_ROOT . 'typo3temp/var/tests/acceptance';
        putenv('TYPO3_PATH_ROOT=' . $instancePath);
        putenv('TYPO3_PATH_APP=' . $instancePath);
        $testbase->setTypo3TestingContext();

        $testbase->removeOldInstanceIfExists($instancePath);
        // Basic instance directory structure
        $testbase->createDirectory($instancePath . '/fileadmin');
        $testbase->createDirectory($instancePath . '/typo3temp/var/transient');
        $testbase->createDirectory($instancePath . '/typo3temp/assets');
        $testbase->createDirectory($instancePath . '/typo3conf/ext');
        // Additionally requested directories
        foreach ($this->config['additionalFoldersToCreate'] as $directory) {
            $testbase->createDirectory($instancePath . '/' . $directory);
        }
        $testbase->setUpInstanceCoreLinks($instancePath);
        $testExtensionsToLoad = $this->config['testExtensionsToLoad'];
        $testbase->linkTestExtensionsToInstance($instancePath, $testExtensionsToLoad);
        $testbase->linkPathsInTestInstance($instancePath, $this->config['pathsToLinkInTestInstance']);
        $localConfiguration['DB'] = $testbase->getOriginalDatabaseSettingsFromEnvironmentOrLocalConfiguration($this->config);
        $dbDriver = $localConfiguration['DB']['Connections']['Default']['driver'];
        $originalDatabaseName = '';
        if ($dbDriver !== 'pdo_sqlite') {
            $this->output->debug('Database Connection: ' . json_encode($localConfiguration['DB']));
            $originalDatabaseName = $localConfiguration['DB']['Connections']['Default']['dbname'];
            // Append the unique identifier to the base database name to end up with a single database per test case
            $localConfiguration['DB']['Connections']['Default']['dbname'] = $originalDatabaseName . '_at';
            $testbase->testDatabaseNameIsNotTooLong($originalDatabaseName, $localConfiguration);
        } else {
            // sqlite dbs of all tests are stored in a dir parallel to instance roots. Allows defining this path as tmpfs.
            $this->output->debug('Database Connection: ' . json_encode($localConfiguration['DB']));
            $testbase->createDirectory(dirname($instancePath) . '/acceptance-sqlite-dbs');
            $dbPathSqlite = dirname($instancePath) . '/acceptance-sqlite-dbs/test_acceptance.sqlite';
            $localConfiguration['DB']['Connections']['Default']['path'] = $dbPathSqlite;
        }
        // Set some hard coded base settings for the instance. Those could be overruled by
        // $this->config['configurationToUseInTestInstance ']if needed again.
        $localConfiguration['BE']['debug'] = true;
        $localConfiguration['BE']['installToolPassword'] = '$P$notnotnotnotnotnot.validvalidva';
        $localConfiguration['SYS']['displayErrors'] = true;
        $localConfiguration['SYS']['devIPmask'] = '*';
        $localConfiguration['SYS']['exceptionalErrors'] = E_ALL;
        $localConfiguration['SYS']['errorHandlerErrors'] = E_ALL;
        $localConfiguration['SYS']['trustedHostsPattern'] = '.*';
        $localConfiguration['SYS']['encryptionKey'] = 'iAmInvalid';
        $localConfiguration['SYS']['features']['redirects.hitCount'] = true;
        // @todo: This sql_mode should be enabled as soon as styleguide and dataHandler can cope with it
        //$localConfiguration['SYS']['setDBinit'] = 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY\';';
        $localConfiguration['GFX']['processor'] = 'GraphicsMagick';
        $testbase->setUpLocalConfiguration($instancePath, $localConfiguration, $this->config['configurationToUseInTestInstance']);
        $coreExtensionsToLoad = $this->config['coreExtensionsToLoad'];
        $frameworkExtensionPaths = [];
        $testbase->setUpPackageStates($instancePath, [], $coreExtensionsToLoad, $testExtensionsToLoad, $frameworkExtensionPaths);
        $this->output->debug('Loaded Extensions: ' . json_encode(array_merge($coreExtensionsToLoad, $testExtensionsToLoad)));
        $testbase->setUpBasicTypo3Bootstrap($instancePath);
        if ($dbDriver !== 'pdo_sqlite') {
            $testbase->setUpTestDatabase($localConfiguration['DB']['Connections']['Default']['dbname'], $originalDatabaseName);
        } else {
            $testbase->setUpTestDatabase($localConfiguration['DB']['Connections']['Default']['path'], $originalDatabaseName);
        }
        $testbase->loadExtensionTables();
        $testbase->createDatabaseStructure();

        // Unregister core error handler again, which has been initialized by
        // $testbase->setUpBasicTypo3Bootstrap($instancePath); for DB schema
        // migration.
        // @todo: See which other possible state should be dropped here again (singletons, ...?)
        restore_error_handler();

        // Unset a closure or phpunit kicks in with a 'serialization of \Closure is not allowed'
        // Alternative solution:
        // unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['extbase']);
        $suite = $suiteEvent->getSuite();
        $suite->backupGlobals(false);

        foreach ($this->config['csvDatabaseFixtures'] as $fixture) {
            DataSet::import($fixture);
        }
    }
}
