<?php
namespace TYPO3\Flow\Tests\Unit\Resource;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the ResourceTypeConverter class
 *
 */
class ResourceTypeConverterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
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

    public function setUp()
    {
        $this->resourceTypeConverter = $this->getAccessibleMock('TYPO3\Flow\Resource\ResourceTypeConverter', array('dummy'));

        $this->mockPersistenceManager = $this->getMockBuilder('TYPO3\Flow\Persistence\PersistenceManagerInterface')->getMock();
        $this->resourceTypeConverter->_set('persistenceManager', $this->mockPersistenceManager);

        $this->mockResourceManager = $this->getMockBuilder('TYPO3\Flow\Resource\ResourceManager')->getMock();
        $this->resourceTypeConverter->_set('resourceManager', $this->mockResourceManager);
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(array('string', 'array'), $this->resourceTypeConverter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('TYPO3\Flow\Resource\Resource', $this->resourceTypeConverter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->resourceTypeConverter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAnArrayWithErrorSet()
    {
        $this->assertTrue($this->resourceTypeConverter->canConvertFrom(array('error' => \UPLOAD_ERR_OK), 'TYPO3\Flow\Resource\Resource'));
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAnArrayWithSubmittedFileSet()
    {
        $this->assertTrue($this->resourceTypeConverter->canConvertFrom(array('submittedFile' => 'somehash'), 'TYPO3\Flow\Resource\Resource'));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfSourceArrayIsEmpty()
    {
        $this->assertNull($this->resourceTypeConverter->convertFrom(array(), 'TYPO3\Flow\Resource\Resource'));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfNoFileWasUploaded()
    {
        $source = array('error' => \UPLOAD_ERR_NO_FILE);
        $this->assertNull($this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource'));
    }

    /**
     * @test
     */
    public function convertFromReturnsPreviouslyUploadedResourceIfNoNewFileWasUploaded()
    {
        $source = array(
            'error' => \UPLOAD_ERR_NO_FILE,
            'submittedFile' => array(
                'filename' => 'SomeFilename',
                'resourcePointer' => 'someResourcePointer',
            )
        );
        $mockResourcePointer = $this->getMockBuilder('TYPO3\Flow\Resource\ResourcePointer')->disableOriginalConstructor()->getMock();
        $this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('someResourcePointer', 'TYPO3\Flow\Resource\ResourcePointer')->will($this->returnValue($mockResourcePointer));
        $resource = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource');
        $this->assertInstanceOf('TYPO3\Flow\Resource\Resource', $resource);
        $this->assertSame($mockResourcePointer, $resource->getResourcePointer());
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfSpecifiedResourcePointerCantBeFound()
    {
        $source = array(
            'error' => \UPLOAD_ERR_NO_FILE,
            'submittedFile' => array(
                'filename' => 'SomeFilename',
                'resourcePointer' => 'someNonExistingResourcePointer',
            )
        );
        $this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('someNonExistingResourcePointer', 'TYPO3\Flow\Resource\ResourcePointer')->will($this->returnValue(null));
        $this->assertNull($this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource'));
    }

    /**
     * @test
     */
    public function convertFromReturnsAnErrorIfFileUploadFailed()
    {
        $source = array(
            'error' => \UPLOAD_ERR_PARTIAL
        );

        $actualResult = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource');
        $this->assertInstanceOf('TYPO3\Flow\Error\Error', $actualResult);
    }

    /**
     * @test
     */
    public function convertFromAddsSystemLogEntryIfFileUploadFailedDueToAServerError()
    {
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
    public function convertFromImportsResourceIfFileUploadSucceeded()
    {
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
    public function convertFromReturnsAnErrorIfTheUploadedFileCantBeImported()
    {
        $source = array(
            'tmp_name' => 'SomeFilename',
            'error' => \UPLOAD_ERR_OK
        );
        $this->mockResourceManager->expects($this->once())->method('importUploadedResource')->with($source)->will($this->returnValue(false));

        $actualResult = $this->resourceTypeConverter->convertFrom($source, 'TYPO3\Flow\Resource\Resource');
        $this->assertInstanceOf('TYPO3\Flow\Error\Error', $actualResult);
    }
}
