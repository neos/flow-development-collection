<?php
namespace Neos\Flow\Tests\Unit\ResourceManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceTypeConverter;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Error\Messages as FlowError;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;

/**
 * Test case for the ResourceTypeConverter class
 */
class ResourceTypeConverterTest extends UnitTestCase
{
    /**
     * @var ResourceTypeConverter
     */
    protected $resourceTypeConverter;

    /**
     * @var PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    /**
     * @var ResourceManager
     */
    protected $mockResourceManager;

    public function setUp()
    {
        $this->resourceTypeConverter = $this->getAccessibleMock(ResourceTypeConverter::class, ['dummy']);

        $this->mockPersistenceManager = $this->getMockBuilder(PersistenceManagerInterface::class)->getMock();
        $this->resourceTypeConverter->_set('persistenceManager', $this->mockPersistenceManager);

        $this->mockResourceManager = $this->getMockBuilder(ResourceManager::class)->getMock();
        $this->resourceTypeConverter->_set('resourceManager', $this->mockResourceManager);
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['string', 'array', UploadedFileInterface::class], $this->resourceTypeConverter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals(PersistentResource::class, $this->resourceTypeConverter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->resourceTypeConverter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAnArrayWithErrorSet()
    {
        $this->assertTrue($this->resourceTypeConverter->canConvertFrom(['error' => \UPLOAD_ERR_OK], PersistentResource::class));
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAnArrayWithOriginallySubmittedResourceSet()
    {
        $this->assertTrue($this->resourceTypeConverter->canConvertFrom(['originallySubmittedResource' => 'SomeResource'], PersistentResource::class));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfSourceArrayIsEmpty()
    {
        $this->assertNull($this->resourceTypeConverter->convertFrom([], PersistentResource::class));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfNoFileWasUploaded()
    {
        $source = ['error' => \UPLOAD_ERR_NO_FILE];
        $this->assertNull($this->resourceTypeConverter->convertFrom($source, PersistentResource::class));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfNoFileWasUploadedAndEmptyHashIsSet()
    {
        $source = ['error' => \UPLOAD_ERR_NO_FILE, 'hash' => ''];
        $this->assertNull($this->resourceTypeConverter->convertFrom($source, PersistentResource::class));
    }

    /**
     * @test
     */
    public function convertFromReturnsPreviouslyUploadedResourceIfNoNewFileWasUploaded()
    {
        $source = [
            'error' => \UPLOAD_ERR_NO_FILE,
            'originallySubmittedResource' => [
                '__identity' => '79ecda60-1a27-69ca-17bf-a5d9e80e6c39'
            ]
        ];

        $expectedResource = new PersistentResource();
        $this->inject($this->resourceTypeConverter, 'persistenceManager', $this->mockPersistenceManager);
        $this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('79ecda60-1a27-69ca-17bf-a5d9e80e6c39', PersistentResource::class)->willReturn($expectedResource);

        $actualResource = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);

        $this->assertInstanceOf(PersistentResource::class, $actualResource);
        $this->assertSame($expectedResource, $actualResource);
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfSpecifiedResourceCantBeFound()
    {
        $source = [
            'error' => \UPLOAD_ERR_NO_FILE,
            'originallySubmittedResource' => [
                '__identity' => '79ecda60-1a27-69ca-17bf-a5d9e80e6c39'
            ]
        ];

        $this->inject($this->resourceTypeConverter, 'persistenceManager', $this->mockPersistenceManager);
        $this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('79ecda60-1a27-69ca-17bf-a5d9e80e6c39', PersistentResource::class)->willReturn(null);

        $actualResource = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);

        $this->assertNull($actualResource);
    }

    /**
     * @test
     */
    public function convertFromReturnsAnErrorIfFileUploadFailed()
    {
        $source = [
            'error' => \UPLOAD_ERR_PARTIAL
        ];

        $actualResult = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
        $this->assertInstanceOf(FlowError\Error::class, $actualResult);
    }

    /**
     * @test
     */
    public function convertFromAddsSystemLogEntryIfFileUploadFailedDueToAServerError()
    {
        $source = [
            'error' => \UPLOAD_ERR_CANT_WRITE
        ];

        $mockSystemLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $mockSystemLogger->expects($this->once())->method('error');
        $this->resourceTypeConverter->injectLogger($mockSystemLogger);

        $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
    }

    /**
     * @test
     */
    public function convertFromImportsResourceIfFileUploadSucceeded()
    {
        $source = [
            'tmp_name' => 'SomeFilename',
            'name' => 'UploadedFilename',
            'size' => 100,
            'error' => \UPLOAD_ERR_OK
        ];

        $result = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
        $this->assertInstanceOf(PersistentResource::class, $result);
        $this->assertEquals($source['name'], $result->getFilename());
        $this->assertEquals($source['size'], $result->getFileSize());
    }
}
