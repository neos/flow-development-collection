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
 */
class Lock
{
    /**
     * @var LockManager
     */
    protected static $lockManager;

    /**
     * @var \TYPO3\Flow\Utility\Lock\LockStrategyInterface
     */
    protected $lockStrategy;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var boolean
     */
    protected $exclusiveLock;

    /**
     * @param string $subject
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock. An exclusive lock is the default.
     */
    public function __construct($subject, $exclusiveLock = true)
    {
        if (self::$lockManager === null) {
            return;
        }

        $this->subject = $subject;
        $this->exclusiveLock = $exclusiveLock;

        $this->lockStrategy = self::$lockManager->getLockStrategyInstance();
        $this->lockStrategy->acquire($subject, $exclusiveLock);
    }

    /**
     * @return \TYPO3\Flow\Utility\Lock\LockStrategyInterface
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
     * @deprecated Use \TYPO3\Flow\Utility\Lock\Factory::acquireCallback
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
