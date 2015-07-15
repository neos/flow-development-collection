<?php
namespace TYPO3\Flow\Tests\Unit\Resource\Storage;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use TYPO3\Flow\Resource\Storage\WritableFileSystemStorage;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Utility\Environment;

/**
 * Test case for the WritableFileSystemStorage class
 */
class WritableFileSystemStorageTest extends UnitTestCase {

	/**
	 * @var WritableFileSystemStorage|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $writableFileSystemStorage;

	/**
	 * @var vfsStreamDirectory
	 */
	protected $mockDirectory;

	/**
	 * @var Environment|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockEnvironment;

	public function setUp() {
		$this->mockDirectory = vfsStream::setup('WritableFileSystemStorageTest');

		$this->writableFileSystemStorage = $this->getAccessibleMock(WritableFileSystemStorage::class, NULL, ['testStorage', ['path' => 'vfs://WritableFileSystemStorageTest/']]);

		$this->mockEnvironment = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
		$this->mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://WritableFileSystemStorageTest/'));
		$this->inject($this->writableFileSystemStorage, 'environment', $this->mockEnvironment);
	}

	/**
	 * @test
	 */
	public function importTemporaryFileFixesPermissionsForTemporaryFile() {
		$mockTempFile = vfsStream::newFile('SomeTemporaryFile', 0333)
			->withContent('fixture')
			->at($this->mockDirectory);
		$this->writableFileSystemStorage->_call('importTemporaryFile', $mockTempFile->url(), 'default');

		// dummy assertion to suppress PHPUnit warning
		$this->assertTrue(TRUE);
	}

}