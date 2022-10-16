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
use Codeception\Events;
use Codeception\Extension;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\Documentation\Screenshots\Core\Functional\Framework\DataHandling\DataSet;
use TYPO3\Documentation\Screenshots\Core\Testbase;
use function PHPUnit\Framework\throwException;

/**
 * This codeception extension creates a full TYPO3 instance within
 * typo3temp. Own acceptance test suites should extend from this class
 * and change the properties. This can be used to not copy the whole
 * bootstrapTypo3Environment() method but reuse it instead.
 */
abstract class AbstractBackendSuiteEnvironment extends AbstractBasicBackendEnvironment
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

    }
}
