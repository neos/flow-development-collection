<?php
namespace TYPO3\Flow\Tests\Functional\Utility\Lock;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Utility\Exception\LockNotAcquiredException;
use TYPO3\Flow\Utility\Lock\Lock;

/**
 * Functional test for the Lock class
 */
class LockTest extends FunctionalTestCase
{
    /**
     * @var string
     */
    protected $lockFileName;


    public function setUp()
    {
        parent::setUp();

        $lock = new Lock('testLock');
        $this->lockFileName = $lock->getLockStrategy()->getLockFileName();
        $lock->release();
    }

    /**
     * @test
     */
    public function lockCanBeAcquiredAndReleased()
    {
        try {
            $lock = $this->objectManager->get(Lock::class, 'testLock');
            $lock->release();
            $lock = $this->objectManager->get(Lock::class, 'testLock');
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
        $lock = $this->objectManager->get(Lock::class, 'testLock');
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
