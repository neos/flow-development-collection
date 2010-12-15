<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Core;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Testcase for the Modification Time Change Detection Strategy
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class LockManagerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('TestDirectory'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectSetsTheLockedStatusToTrueIfALockFileExists() {
		$temporaryDirectoryUrl = \vfsStream::url('TestDirectory') . '/';
		file_put_contents($temporaryDirectoryUrl . 'FLOW3.lock', '');

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment');
		$mockEnvironment->expects($this->once())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryUrl));

		$lockManager = new \F3\FLOW3\Core\LockManager();
		$lockManager->injectEnvironment($mockEnvironment);
		$lockManager->initializeObject();

		$this->assertTrue($lockManager->isSiteLocked());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectRemovesALockFileIfItsOlderThanTheMaximumTime() {
		$temporaryDirectoryUrl = \vfsStream::url('TestDirectory') . '/';
		file_put_contents($temporaryDirectoryUrl . 'FLOW3.lock', '');
		\vfsStreamWrapper::getRoot()->getChild('FLOW3.lock')->setFilemtime(time() - \F3\FLOW3\Core\LockManager::LOCKFILE_MAXIMUM_AGE - 2);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment');
		$mockEnvironment->expects($this->once())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryUrl));

		$lockManager = new \F3\FLOW3\Core\LockManager();
		$lockManager->injectEnvironment($mockEnvironment);
		$lockManager->initializeObject();

		$this->assertFalse($lockManager->isSiteLocked());
		$this->assertFalse(file_exists($temporaryDirectoryUrl . 'FLOW3.lock'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function lockSiteCreatesALockFileAndSetsTheStatusToLocked() {
		$temporaryDirectoryUrl = \vfsStream::url('TestDirectory') . '/';

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment');
		$mockEnvironment->expects($this->once())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryUrl));

		$mockLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$lockManager = new \F3\FLOW3\Core\LockManager();
		$lockManager->injectEnvironment($mockEnvironment);
		$lockManager->injectSystemLogger($mockLogger);
		$lockManager->initializeObject();

		$lockManager->lockSite();

		$this->assertTrue($lockManager->isSiteLocked());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unlockSiteRemovesTheLockFileAndResetsTheLockStatus() {
		$temporaryDirectoryUrl = \vfsStream::url('TestDirectory') . '/';
		file_put_contents($temporaryDirectoryUrl . 'FLOW3.lock', '');

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment');
		$mockEnvironment->expects($this->once())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryUrl));

		$mockLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$lockManager = new \F3\FLOW3\Core\LockManager();
		$lockManager->injectEnvironment($mockEnvironment);
		$lockManager->injectSystemLogger($mockLogger);
		$lockManager->initializeObject();

		$lockManager->unlockSite();

		$this->assertFalse($lockManager->isSiteLocked());
		$this->assertFalse(file_exists($temporaryDirectoryUrl . 'FLOW3.lock'));
	}
}
?>