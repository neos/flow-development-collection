<?php
namespace TYPO3\Flow\Utility\Exception;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A lock not created exception
 *
 * This exception is thrown by locking strategies if the lock could not be acquired.
 *
 */
class LockNotAcquiredException extends \TYPO3\Flow\Exception
{
}
