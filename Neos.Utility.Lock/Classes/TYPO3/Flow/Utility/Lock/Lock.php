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

/**
 * A general lock class.
 *
 * @api
 */
class Lock
{
    /**
     * @var string
     */
    protected static $lockStrategyClassName;

    /**
     * @var LockManager
     */
    protected static $lockManager;

    /**
     * @var LockStrategyInterface
     */
    protected $lockStrategy;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var boolean
     */
    protected $exclusiveLock = true;

    /**
     * @param string $subject
     * @param \Closure $callback
     */
    public static function synchronized($subject, \Closure $callback)
    {
        if (self::$lockManager === null) {
            return;
        }
        $strategy = self::$lockManager->getLockStrategyInstance();
        $strategy->synchronized($subject, $callback);
    }

    /**
     * Set the instance of LockManager to use.
     *
     * Must be nullable especially for testing
     *
     * @param LockManager $lockManager
     */
    public static function setLockManager(LockManager $lockManager = null)
    {
        static::$lockManager = $lockManager;
    }
}
