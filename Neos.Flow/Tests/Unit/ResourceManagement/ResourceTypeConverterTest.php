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

    protected function setUp(): void
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
        self::assertEquals(['string', 'array', UploadedFileInterface::class], $this->resourceTypeConverter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals(PersistentResource::class, $this->resourceTypeConverter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->resourceTypeConverter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAnArrayWithErrorSet()
    {
        self::assertTrue($this->resourceTypeConverter->canConvertFrom(['error' => \UPLOAD_ERR_OK], PersistentResource::class));
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAnArrayWithOriginallySubmittedResourceSet()
    {
        self::assertTrue($this->resourceTypeConverter->canConvertFrom(['originallySubmittedResource' => 'SomeResource'], PersistentResource::class));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfSourceArrayIsEmpty()
    {
        self::assertNull($this->resourceTypeConverter->convertFrom([], PersistentResource::class));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfNoFileWasUploaded()
    {
        $source = ['error' => \UPLOAD_ERR_NO_FILE];
        self::assertNull($this->resourceTypeConverter->convertFrom($source, PersistentResource::class));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfNoFileWasUploadedAndEmptyHashIsSet()
    {
        $source = ['error' => \UPLOAD_ERR_NO_FILE, 'hash' => ''];
        self::assertNull($this->resourceTypeConverter->convertFrom($source, PersistentResource::class));
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
        $this->mockPersistenceManager->expects(self::once())->method('getObjectByIdentifier')->with('79ecda60-1a27-69ca-17bf-a5d9e80e6c39', PersistentResource::class)->will(self::returnValue($expectedResource));

        $actualResource = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);

        self::assertInstanceOf(PersistentResource::class, $actualResource);
        self::assertSame($expectedResource, $actualResource);
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
        $this->mockPersistenceManager->expects(self::once())->method('getObjectByIdentifier')->with('79ecda60-1a27-69ca-17bf-a5d9e80e6c39', PersistentResource::class)->will(self::returnValue(null));

        $actualResource = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);

        self::assertNull($actualResource);
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
        self::assertInstanceOf(FlowError\Error::class, $actualResult);
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
        $mockSystemLogger->expects(self::once())->method('error');
        $this->inject($this->resourceTypeConverter, 'logger', $mockSystemLogger);

        $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
    }

    /**
     * @test
     */
    public function convertFromImportsResourceIfFileUploadSucceeded()
    {
        $source = [
            'tmp_name' => 'SomeFilename',
            'error' => \UPLOAD_ERR_OK
        ];
        $mockResource = $this->getMockBuilder(PersistentResource::class)->getMock();
        $this->mockResourceManager->expects(self::once())->method('importUploadedResource')->with($source)->will(self::returnValue($mockResource));

        $actualResult = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
        self::assertSame($mockResource, $actualResult);
    }

    /**
     * @test
     */
    public function convertFromReturnsAnErrorIfTheUploadedFileCantBeImported()
    {
        $this->inject($this->resourceTypeConverter, 'logger', $this->createMock(LoggerInterface::class));

        $source = [
            'tmp_name' => 'SomeFilename',
            'error' => \UPLOAD_ERR_OK
        ];
        $this->mockResourceManager->expects(self::once())->method('importUploadedResource')->with($source)->will(self::throwException(new Exception()));

        $actualResult = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
        self::assertInstanceOf(FlowError\Error::class, $actualResult);
    }
}
