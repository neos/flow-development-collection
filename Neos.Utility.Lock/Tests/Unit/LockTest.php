<?php
namespace Neos\Utility\Lock\Tests\Unit;

/*
 * This file is part of the Neos.Utility.Lock package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use Neos\Utility\Lock\FlockLockStrategy;
use Neos\Utility\Lock\Lock;
use Neos\Utility\Lock\LockManager;
use Neos\Utility\Lock\LockNotAcquiredException;

/**
 * Strictly a functional test for the Lock class and FlockLockStrategy.
 */
class LockTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $lockFileName;

    public static function setUpBeforeClass()
    {
        vfsStream::setup('Lock');

        $lockManager = new LockManager(FlockLockStrategy::class, [
            'lockDirectory' => 'vfs://Lock'
        ]);
        Lock::setLockManager($lockManager);
    }

    public function setUp()
    {
        $lock = new Lock('testLock');
        $this->lockFileName = $lock->getLockStrategy()->getLockFileName();
        $lock->release();
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
     */
    public function writeLockLocksExclusively()
    {
        $lock = new Lock('testLock');
        $this->assertExclusivelyLocked($lock);
        $this->assertTrue($lock->release());

        $lock = new Lock('testLock');
        $this->assertExclusivelyLocked($lock);
        $this->assertTrue($lock->release());
    }

    /**
     * @test
     */
    public function readLockCanBeAcquiredTwice()
    {
        $lock1 = new Lock('testLock', false);
        $lock2 = new Lock('testLock', false);

        $this->assertTrue($lock1->release(), 'Lock 1 could not be released');
        $this->assertTrue($lock2->release(), 'Lock 2 could not be released');
    }

    /**
     * @param string $message
     */
    protected function assertExclusivelyLocked($message = '')
    {
        $lockFilePointer = fopen($this->lockFileName, 'w');
        $this->assertFalse(flock($lockFilePointer, LOCK_EX | LOCK_NB), $message);
        fclose($lockFilePointer);
    }
}
