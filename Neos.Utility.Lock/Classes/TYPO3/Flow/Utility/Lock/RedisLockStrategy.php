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
     * @var \Redis
     */
    protected static $redis;

    public function __construct()
    {
        if (self::$redis === null) {
            self::$redis = new \Redis();
            self::$redis->connect('localhost');
        }
    }

    /**
     * @param string $subject
     * @param \Closure $callback
     * @return mixed Return value of the callback
     */
    public function synchronized($subject, \Closure $callback)
    {
        $mutex = new PHPRedisMutex([self::$redis], $subject);
        return $mutex->synchronized($callback);
    }
}
