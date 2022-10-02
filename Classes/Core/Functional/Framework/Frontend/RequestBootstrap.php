<?php

declare(strict_types=1);
namespace TYPO3\Documentation\Screenshots\Core\Functional\Framework\Frontend;

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

use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * Bootstrap for direct CLI Request
 *
 * @internal
 * @deprecated This class should be dropped or heavily reduced when retrieveFrontendRequestResult() is dropped.
 */
class RequestBootstrap
{
    /**
     * @return InternalRequest|null
     */
    public static function getInternalRequest(): ?InternalRequest
    {
        return $_SERVER['X_TYPO3_TESTING_FRAMEWORK']['request'] ?? null;
    }
}