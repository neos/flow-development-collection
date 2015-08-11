<?php
namespace TYPO3\Flow\Tests\Unit\Resource;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the ResourceTypeConverter class
 */
class ResourceTypeConverterTest extends UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Resource\ResourceTypeConverter
	 */
	protected $resourceTypeConverter;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $mockResourceManager;

	public function setUp() {
		$this->resourceTypeConverter = $this->getAccessibleMock('TYPO3\Flow\Resource\ResourceTypeConverter', array('dummy'));

		$this->mockPersistenceManager = $this->getMockBuilder('TYPO3\Flow\Persistence\PersistenceManagerInterface')->getMock();
		$this->resourceTypeConverter->_set('persistenceManager', $this->mockPersistenceManager);

		$this->mockResourceManager = $this->getMockBuilder('TYPO3\Flow\Resource\ResourceManager')->getMock();
		$this->resourceTypeConverter->_set('resourceManager', $this->mockResourceManager);
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('string', 'array'), $this->resourceTypeConverter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('TYPO3\Flow\Resource\Resource', $this->resourceTypeConverter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->resourceTypeConverter->getPriority(), 'Priority does not match');
	}

	/**
	 * @test
	 */
	public function canConvertFromReturnsTrueIfSourceTypeIsAnArrayWithErrorSet() {
		$this->assertTrue($this->resourceTypeConverter->canConvertFrom(array('error' => \UPLOAD_ERR_OK), 'TYPO3\Flow\Resource\Resource'));
	}

	/**
	 * @test
	 */
	public function canConvertFromReturnsTrueIfSourceTypeIsAnArrayWithOriginallySubmittedResourceSet() {
		$this->assertTrue($this->resourceTypeConverter->canConvertFrom(array('originallySubmittedResource' => 'SomeResource'), 'TYPO3\Flow\Resource\Resource'));
	}

	/**
	 * @test
	 */
	public function convertFromReturnsNullIfSourceArrayIsEmpty() {
		$this->assertNull($this->resourceTypeConverter->convertFrom(array(), 'TYPO3\Flow\Resource\Resource'));
	}

	/**
	 * @test
	 */
	public function convertFromReturnsNullIfNoFileWasUploaded() {
		$source = array('error' => \UPLOAD_ERR_NO_FILE);
		$this->assertNull($this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource'));
	}

	/**
	 * @test
	 */
	public function convertFromReturnsPreviouslyUploadedResourceIfNoNewFileWasUploaded() {
		$source = array(
			'error' => \UPLOAD_ERR_NO_FILE,
			'originallySubmittedResource' => array(
				'__identity' => '79ecda60-1a27-69ca-17bf-a5d9e80e6c39'
			)
		);

		$expectedResource = new Resource();
		$this->inject($this->resourceTypeConverter, 'persistenceManager', $this->mockPersistenceManager);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('79ecda60-1a27-69ca-17bf-a5d9e80e6c39', 'TYPO3\Flow\Resource\Resource')->will($this->returnValue($expectedResource));

		$actualResource = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource');

		$this->assertInstanceOf('TYPO3\Flow\Resource\Resource', $actualResource);
		$this->assertSame($expectedResource, $actualResource);
	}

	/**
	 * @test
	 */
	public function convertFromReturnsNullIfSpecifiedResourceCantBeFound() {
		$source = array(
			'error' => \UPLOAD_ERR_NO_FILE,
			'originallySubmittedResource' => array(
					'__identity' => '79ecda60-1a27-69ca-17bf-a5d9e80e6c39'
			)
		);

		$this->inject($this->resourceTypeConverter, 'persistenceManager', $this->mockPersistenceManager);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('79ecda60-1a27-69ca-17bf-a5d9e80e6c39', 'TYPO3\Flow\Resource\Resource')->will($this->returnValue(NULL));

		$actualResource = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource');

		$this->assertNull($actualResource);
	}

	/**
	 * @test
	 */
	public function convertFromReturnsAnErrorIfFileUploadFailed() {
		$source = array(
			'error' => \UPLOAD_ERR_PARTIAL
		);

		$actualResult = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource');
		$this->assertInstanceOf('TYPO3\Flow\Error\Error', $actualResult);
	}

	/**
	 * @test
	 */
	public function convertFromAddsSystemLogEntryIfFileUploadFailedDueToAServerError() {
		$source = array(
			'error' => \UPLOAD_ERR_CANT_WRITE
		);

		$mockSystemLogger = $this->getMockBuilder('TYPO3\Flow\Log\SystemLoggerInterface')->getMock();
		$mockSystemLogger->expects($this->once())->method('log');
		$this->resourceTypeConverter->_set('systemLogger', $mockSystemLogger);

		$this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource');
	}


	/**
	 * @test
	 */
	public function convertFromImportsResourceIfFileUploadSucceeded() {
		$source = array(
			'tmp_name' => 'SomeFilename',
			'error' => \UPLOAD_ERR_OK
		);
		$mockResource = $this->getMockBuilder('TYPO3\Flow\Resource\Resource')->getMock();
		$this->mockResourceManager->expects($this->once())->method('importUploadedResource')->with($source)->will($this->returnValue($mockResource));

		$actualResult = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource');
		$this->assertSame($mockResource, $actualResult);
	}

	/**
	 * @test
	 */
	public function convertFromReturnsAnErrorIfTheUploadedFileCantBeImported() {
		$source = array(
			'tmp_name' => 'SomeFilename',
			'error' => \UPLOAD_ERR_OK
		);
		$this->mockResourceManager->expects($this->once())->method('importUploadedResource')->with($source)->will($this->returnValue(FALSE));

		$actualResult = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource');
		$this->assertInstanceOf('TYPO3\Flow\Error\Error', $actualResult);
	}
}
