<?php
namespace TYPO3\Flow\Tests\Unit\Monitor\ChangeDetectionStrategy;

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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Testcase for the Modification Time Change Detection Strategy
 *
 */
class ModificationTimeStrategyTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ModificationTime
	 */
	protected $strategy;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 */
	public function setUp() {
		vfsStream::setup('testDirectory');

		$this->cache = $this->getMock('TYPO3\Flow\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);

		$this->strategy = new \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy();
		$this->strategy->injectCache($this->cache);
	}

	/**
	 * @test
	 */
	public function getFileStatusReturnsStatusUnchangedIfFileDoesNotExistAndDidNotExistEarlier() {
		$fileUrl = vfsStream::url('testDirectory') . '/test.txt';

		$status = $this->strategy->getFileStatus($fileUrl);
		$this->assertSame(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED, $status);
	}

	/**
	 * @test
	 */
	public function getFileStatusReturnsStatusUnchangedIfFileExistedAndTheModificationTimeDidNotChange() {
		$fileUrl = vfsStream::url('testDirectory') . '/test.txt';
		file_put_contents($fileUrl, 'test data');

		$this->strategy->getFileStatus($fileUrl);
		clearstatcache();
		$status = $this->strategy->getFileStatus($fileUrl);

		$this->assertSame(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_UNCHANGED, $status);
	}

	/**
	 * @test
	 */
	public function getFileStatusDetectsANewlyCreatedFile() {
		$fileUrl = vfsStream::url('testDirectory') . '/test.txt';
		file_put_contents($fileUrl, 'test data');

		$status = $this->strategy->getFileStatus($fileUrl);
		$this->assertSame(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CREATED, $status);
	}

	/**
	 * @test
	 */
	public function getFileStatusDetectsADeletedFile() {
		$fileUrl = vfsStream::url('testDirectory') . '/test.txt';
		file_put_contents($fileUrl, 'test data');

		$this->strategy->getFileStatus($fileUrl);
		unlink($fileUrl);
		$status = $this->strategy->getFileStatus($fileUrl);

		$this->assertSame(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_DELETED, $status);
	}

	/**
	 * @test
	 */
	public function getFileStatusReturnsStatusChangedIfTheFileExistedEarlierButTheModificationTimeHasChangedSinceThen() {
		$fileUrl = vfsStream::url('testDirectory') . '/test.txt';
		file_put_contents($fileUrl, 'test data');

		$this->strategy->getFileStatus($fileUrl);
		vfsStreamWrapper::getRoot()->getChild('test.txt')->lastModified(time() + 5);
		clearstatcache();
		$status = $this->strategy->getFileStatus($fileUrl);

		$this->assertSame(\TYPO3\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface::STATUS_CHANGED, $status);
	}
}
?>