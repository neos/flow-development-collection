<?php
namespace TYPO3\Flow\Utility\Lock\Tests\Unit;

/*
 * This file is part of the Neos.Utility.Lock package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Utility\Lock\SemaphoreLockStrategy;
use TYPO3\Flow\Utility\Lock\Lock;
use TYPO3\Flow\Utility\Lock\LockManager;
use TYPO3\Flow\Utility\Lock\LockNotAcquiredException;
use TYPO3\Flow\Utility\Lock\UnsupportedStrategyException;

/**
 * Strictly a functional test for the Lock class and SemaphoreLockStrategy.
 */
class SemaphoreLockTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $lockManager = new LockManager(SemaphoreLockStrategy::class);
        Lock::setLockManager($lockManager);
        try {
            $lock = new Lock('testLock');
        } catch (UnsupportedStrategyException $e) {
            self::markTestSkipped('The SemaphoreLockStrategy is not available.');
        } finally {
            $lock->release();
        }
    }

    public static function tearDownAfterClass()
    {
        Lock::setLockManager(null);
    }

    /**
     * @test
     */
    public function lockCanBeAcquiredAndReleased()
    {
        try {
            $lock = new Lock('testLock');
            $lock->release();
            $lock = new Lock('testLock');
        } catch (LockNotAcquiredException $exception) {
            $this->fail('Lock could not be acquired after it was released');
        }

        $this->assertTrue($lock->release());
    }

    /**
     * @test
     * @expectedExecption LockNotAcquiredException
     */
    public function writeLockLocksExclusively()
    {
        $lock1 = new Lock('testLock');
        try {
            $lock2 = new Lock('testLock');
        } finally {
            $lock2->release();
            $lock1->release();
        }
    }
}
