<?php
namespace Neos\Utility\Lock;

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
     * @var \Neos\Utility\Lock\LockStrategyInterface
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
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock. An exclusive lock ist the default.
     */
    public function __construct($subject, $exclusiveLock = true)
    {
        if (self::$lockManager === null) {
            return;
        }
        $this->lockStrategy = self::$lockManager->getLockStrategyInstance();
        $this->lockStrategy->acquire($subject, $exclusiveLock);
    }

    /**
     * @return LockStrategyInterface
     */
    public function getLockStrategy()
    {
        return $this->lockStrategy;
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

    /**
     * Releases the lock
     * @return boolean TRUE on success, FALSE otherwise
     */
    public function release()
    {
        if ($this->lockStrategy instanceof LockStrategyInterface) {
            return $this->lockStrategy->release();
        }
        return true;
    }

    /**
     * Destructor, releases the lock
     * @return void
     */
    public function __destruct()
    {
        $this->release();
    }
}
