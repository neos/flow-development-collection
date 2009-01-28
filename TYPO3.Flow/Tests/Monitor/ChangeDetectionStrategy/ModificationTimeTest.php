<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Monitor\ChangeDetectionStrategy;

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
 * @package FLOW3
 * @subpackage Monitor
 * @version $Id$
 */

require_once('vfs/vfsStream.php');

/**
 * Testcase for the Modification Time Change Detection Strategy
 *
 * @package FLOW3
 * @subpackage Monitor
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ModificationTimeTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Monitor\ChangeDetectionStrategy\ModificationTime
	 */
	protected $strategy;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));

		$this->cache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);

		$this->strategy = new \F3\FLOW3\Monitor\ChangeDetectionStrategy\ModificationTime();
		$this->strategy->injectCache($this->cache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFileStatusReturnsStatusUnchangedIfFileDoesNotExistAndDidNotExistEarlier() {
		$fileURL = \vfsStream::url('testDirectory') . '/test.txt';

		$status = $this->strategy->getFileStatus($fileURL);
		$this->assertSame(\F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_UNCHANGED, $status);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFileStatusReturnsStatusUnchangedIfFileExistedAndTheModificationTimeDidNotChange() {
		$fileURL = \vfsStream::url('testDirectory') . '/test.txt';
		file_put_contents($fileURL, 'test data');

		$this->strategy->getFileStatus($fileURL);
		clearstatcache();
		$status = $this->strategy->getFileStatus($fileURL);

		$this->assertSame(\F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_UNCHANGED, $status);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFileStatusDetectsANewlyCreatedFile() {
		$fileURL = \vfsStream::url('testDirectory') . '/test.txt';
		file_put_contents($fileURL, 'test data');

		$status = $this->strategy->getFileStatus($fileURL);
		$this->assertSame(\F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_CREATED, $status);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFileStatusDetectsADeletedFile() {
		$fileURL = \vfsStream::url('testDirectory') . '/test.txt';
		file_put_contents($fileURL, 'test data');

		$this->strategy->getFileStatus($fileURL);
		unlink($fileURL);
		$status = $this->strategy->getFileStatus($fileURL);

		$this->assertSame(\F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_DELETED, $status);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFileStatusReturnsStatusChangedIfTheFileExistedEarlierButTheModificationTimeHasChangedSinceThen() {
		$fileURL = \vfsStream::url('testDirectory') . '/test.txt';
		file_put_contents($fileURL, 'test data');

		$this->strategy->getFileStatus($fileURL);
		\vfsStreamWrapper::getRoot()->getChild('test.txt')->setFilemtime(time() + 5);
		clearstatcache();
		$status = $this->strategy->getFileStatus($fileURL);

		$this->assertSame(\F3\FLOW3\Monitor\ChangeDetectionStrategyInterface::STATUS_CHANGED, $status);
	}
}
?>