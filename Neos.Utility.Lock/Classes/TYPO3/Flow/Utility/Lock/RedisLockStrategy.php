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

use malkusch\lock\mutex\PHPRedisMutex;

/**
 * A redis based lock strategy.
 *
 * This lock strategy is based on Flock.
 */
class RedisLockStrategy implements LockStrategyInterface
{
    /**
     * @var string
     */
    protected $temporaryDirectory;

    /**
     * File pointer if using flock method
     *
     * @var resource
     */
    protected $filePointer;

    /**
     * @param string $subject
     * @param \Closure $callback
     * @return mixed Return value of the callback
     */
    public function synchronized($subject, \Closure $callback)
    {
        $redis = new \Redis();
        $redis->connect('localhost');

        $mutex = new PHPRedisMutex([$redis], $subject);
        return $mutex->synchronized($callback);
    }
}
