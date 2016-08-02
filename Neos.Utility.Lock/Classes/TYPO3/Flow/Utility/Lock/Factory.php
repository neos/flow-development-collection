<?php
namespace TYPO3\Flow\Utility\Lock;

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
 * A general lock class.
 *
 * @api
 */
class Factory
{
    /**
     * @param string $subject
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock. An exclusive lock is the default.
     * @param \Closure $callback A callback executed before the relase of the lock
     * @return void
     */
    public static function acquireCallback($subject, $exclusiveLock, \Closure $callback)
    {
        $lock = new Lock($subject, $exclusiveLock);
        try {
            $callback();
        } finally {
            $lock->release();
        }
    }
}
