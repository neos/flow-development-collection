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

use malkusch\lock\mutex\MemcachedMutex;
use malkusch\lock\util\DoubleCheckedLocking;

/**
 * A memcached based lock strategy.
 *
 * This lock strategy is based on Flock.
 */
class MemcachedLockStrategy implements LockStrategyInterface
{
    /**
     * @var \Memcached
     */
    protected static $memcached;

    public function __construct(array $options)
    {
        if (self::$memcached === null) {
            self::$memcached = new \Memcached();
            self::$memcached->addServer($options['host'], $options['port']);
        }
    }

    /**
     * @param string $subject
     * @param \Closure $callback
     * @return mixed Return value of the callback
     */
    public function synchronized($subject, \Closure $callback)
    {
        $mutex = new MemcachedMutex($subject, self::$memcached);
        return $mutex->synchronized($callback);
    }

    /**
     * @param string $subject
     * @param \Closure $callback
     * @return DoubleCheckedLocking
     */
    public function check($subject, \Closure $callback)
    {
        $mutex = new MemcachedMutex($subject, self::$memcached);
        return $mutex->check($callback);
    }
}
