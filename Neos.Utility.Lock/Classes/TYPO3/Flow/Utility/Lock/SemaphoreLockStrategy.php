<?php
namespace TYPO3\Flow\Utility\Lock;

/*
 * This file is part of the Neos.Utility.Lock package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use malkusch\lock\mutex\SemaphoreMutex;
use malkusch\lock\util\DoubleCheckedLocking;

/**
 * A semaphore based lock strategy.
 *
 * This lock strategy is based on Flock.
 */
class SemaphoreLockStrategy implements LockStrategyInterface
{
    /**
     * @param string $subject
     * @param \Closure $callback
     * @return mixed Return value of the callback
     */
    public function synchronized($subject, \Closure $callback)
    {
        $semaphore = sem_get(crc32($subject));
        $mutex = new SemaphoreMutex($semaphore);
        return $mutex->synchronized($callback);
    }

    /**
     * @param string $subject
     * @param \Closure $callback
     * @return DoubleCheckedLocking
     */
    public function check($subject, \Closure $callback)
    {
        $semaphore = sem_get(crc32($subject));
        $mutex = new SemaphoreMutex($semaphore);
        return $mutex->check($callback);
    }
}
