<?php
namespace TYPO3\Flow\Tests\Functional\Utility\Lock;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\Utility\Lock\Lock;

/**
 * Functional test for the Lock class
 */
class LockTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var string
	 */
	protected $lockFileName;


	public function setUp() {
		parent::setUp();

		$lock = new Lock('testLock');
		$this->lockFileName = $lock->getLockStrategy()->getLockFileName();
		$lock->release();
	}

	/**
	 * @test
	 */
	public function lockCanBeAcquiredAndReleased() {
		try {
			$lock = $this->objectManager->get('\TYPO3\Flow\Utility\Lock\Lock', 'testLock');
			$lock->release();
			$lock = $this->objectManager->get('\TYPO3\Flow\Utility\Lock\Lock', 'testLock');
		} catch(\TYPO3\Flow\Utility\Exception\LockNotAcquiredException $exception) {
			$this->fail('Lock could not be acquired after it was released');
		}

		$this->assertTrue($lock->release());
	}

	/**
	 * @test
	 */
	public function writeLockLocksExclusively() {
		$lock = $this->objectManager->get('\TYPO3\Flow\Utility\Lock\Lock', 'testLock');
		$this->assertExclusivelyLocked($lock);
		$this->assertTrue($lock->release());

		$lock = new Lock('testLock');
		$this->assertExclusivelyLocked($lock);
		$this->assertTrue($lock->release());
	}

	/**
	 * @test
	 */
	public function readLockCanBeAcquiredTwice() {
		$lock1 = new \TYPO3\Flow\Utility\Lock\Lock('testLock', FALSE);
		$lock2 = new \TYPO3\Flow\Utility\Lock\Lock('testLock', FALSE);

		$this->assertTrue($lock1->release(), 'Lock 1 could not be released');
		$this->assertTrue($lock2->release(), 'Lock 2 could not be released');
	}

	/**
	 * @param string $message
	 */
	protected function assertExclusivelyLocked($message = '') {
		$lockFilePointer = fopen($this->lockFileName, 'w');
		$this->assertFalse(flock($lockFilePointer, LOCK_EX | LOCK_NB), $message);
		fclose($lockFilePointer);
	}
}
