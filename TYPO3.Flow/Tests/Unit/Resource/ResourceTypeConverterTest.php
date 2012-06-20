<?php
namespace TYPO3\FLOW3\Tests\Unit\Resource;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the ResourceTypeConverter class
 *
 */
class ResourceTypeConverterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Resource\ResourceTypeConverter
	 */
	protected $resourceTypeConverter;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @var \TYPO3\FLOW3\Resource\ResourceManager
	 */
	protected $mockResourceManager;

	public function setUp() {
		$this->resourceTypeConverter = $this->getAccessibleMock('TYPO3\FLOW3\Resource\ResourceTypeConverter', array('dummy'));

		$this->mockPersistenceManager = $this->getMockBuilder('TYPO3\FLOW3\Persistence\PersistenceManagerInterface')->getMock();
		$this->resourceTypeConverter->_set('persistenceManager', $this->mockPersistenceManager);

		$this->mockResourceManager = $this->getMockBuilder('TYPO3\FLOW3\Resource\ResourceManager')->getMock();
		$this->resourceTypeConverter->_set('resourceManager', $this->mockResourceManager);
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('array'), $this->resourceTypeConverter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('TYPO3\FLOW3\Resource\Resource', $this->resourceTypeConverter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->resourceTypeConverter->getPriority(), 'Priority does not match');
	}

	/**
	 * @test
	 */
	public function canConvertFromReturnsTrueIfSourceTypeIsAnArray() {
		$this->assertTrue($this->resourceTypeConverter->canConvertFrom(array(), 'TYPO3\FLOW3\Resource\Resource'));
	}

	/**
	 * @test
	 */
	public function convertFromReturnsNullIfSourceArrayIsEmpty() {
		$this->assertNull($this->resourceTypeConverter->convertFrom(array(), 'TYPO3\FLOW3\Resource\Resource'));
	}

	/**
	 * @test
	 */
	public function convertFromReturnsNullIfNoFileWasUploaded() {
		$source = array('error' => \UPLOAD_ERR_NO_FILE);
		$this->assertNull($this->resourceTypeConverter->convertFrom($source, 'TYPO3\FLOW3\Resource\Resource'));
	}

	/**
	 * @test
	 */
	public function convertFromReturnsPreviouslyUploadedResourceIfNoNewFileWasUploaded() {
		$source = array(
			'error' => \UPLOAD_ERR_NO_FILE,
			'submittedFile' => array(
				'filename' => 'SomeFilename',
				'resourcePointer' => 'someResourcePointer',
			)
		);
		$mockResourcePointer = $this->getMockBuilder('TYPO3\FLOW3\Resource\ResourcePointer')->disableOriginalConstructor()->getMock();
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('someResourcePointer', 'TYPO3\FLOW3\Resource\ResourcePointer')->will($this->returnValue($mockResourcePointer));
		$resource = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\FLOW3\Resource\Resource');
		$this->assertInstanceOf('TYPO3\FLOW3\Resource\Resource', $resource);
		$this->assertSame($mockResourcePointer, $resource->getResourcePointer());
	}

	/**
	 * @test
	 */
	public function convertFromReturnsNullIfSpecifiedResourcePointerCantBeFound() {
		$source = array(
			'error' => \UPLOAD_ERR_NO_FILE,
			'submittedFile' => array(
				'filename' => 'SomeFilename',
				'resourcePointer' => 'someNonExistingResourcePointer',
			)
		);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('someNonExistingResourcePointer', 'TYPO3\FLOW3\Resource\ResourcePointer')->will($this->returnValue(NULL));
		$this->assertNull($this->resourceTypeConverter->convertFrom($source, 'TYPO3\FLOW3\Resource\Resource'));
	}

	/**
	 * @test
	 */
	public function convertFromReturnsAnErrorIfFileUploadFailed() {
		$source = array(
			'error' => \UPLOAD_ERR_PARTIAL
		);

		$actualResult = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\FLOW3\Resource\Resource');
		$this->assertInstanceOf('TYPO3\FLOW3\Error\Error', $actualResult);
	}

	/**
	 * @test
	 */
	public function convertFromAddsSystemLogEntryIfFileUploadFailedDueToAServerError() {
		$source = array(
			'error' => \UPLOAD_ERR_CANT_WRITE
		);

		$mockSystemLogger = $this->getMockBuilder('TYPO3\FLOW3\Log\SystemLoggerInterface')->getMock();
		$mockSystemLogger->expects($this->once())->method('log');
		$this->resourceTypeConverter->_set('systemLogger', $mockSystemLogger);

		$this->resourceTypeConverter->convertFrom($source, 'TYPO3\FLOW3\Resource\Resource');
	}


	/**
	 * @test
	 */
	public function convertFromImportsResourceIfFileUploadSucceeded() {
		$source = array(
			'tmp_name' => 'SomeFilename',
			'error' => \UPLOAD_ERR_OK
		);
		$mockResource = $this->getMockBuilder('TYPO3\FLOW3\Resource\Resource')->getMock();
		$this->mockResourceManager->expects($this->once())->method('importUploadedResource')->with($source)->will($this->returnValue($mockResource));

		$actualResult = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\FLOW3\Resource\Resource');
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

		$actualResult = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\FLOW3\Resource\Resource');
		$this->assertInstanceOf('TYPO3\FLOW3\Error\Error', $actualResult);
	}
}

?>
