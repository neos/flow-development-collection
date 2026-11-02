<?php

declare(strict_types=1);

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
use Neos\Error\Messages as FlowError;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceTypeConverter;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;

/**
 * Test case for the ResourceTypeConverter class
 */
final class ResourceTypeConverterTest extends UnitTestCase
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
        $this->resourceTypeConverter = $this->getAccessibleMock(ResourceTypeConverter::class, []);

        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->resourceTypeConverter->_set('persistenceManager', $this->mockPersistenceManager);

        $this->mockResourceManager = $this->createMock(ResourceManager::class);
        $this->resourceTypeConverter->_set('resourceManager', $this->mockResourceManager);
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['string', 'array', UploadedFileInterface::class], $this->resourceTypeConverter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals(PersistentResource::class, $this->resourceTypeConverter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->resourceTypeConverter->getPriority(), 'Priority does not match');
    }

    #[Test]
    public function canConvertFromReturnsTrueIfSourceTypeIsAnArrayWithErrorSet()
    {
        self::assertTrue($this->resourceTypeConverter->canConvertFrom(['error' => \UPLOAD_ERR_OK], PersistentResource::class));
    }

    #[Test]
    public function canConvertFromReturnsTrueIfSourceTypeIsAnArrayWithOriginallySubmittedResourceSet()
    {
        self::assertTrue($this->resourceTypeConverter->canConvertFrom(['originallySubmittedResource' => 'SomeResource'], PersistentResource::class));
    }

    #[Test]
    public function convertFromReturnsNullIfSourceArrayIsEmpty()
    {
        self::assertNull($this->resourceTypeConverter->convertFrom([], PersistentResource::class));
    }

    #[Test]
    public function convertFromReturnsNullIfNoFileWasUploaded()
    {
        $source = ['error' => \UPLOAD_ERR_NO_FILE];
        self::assertNull($this->resourceTypeConverter->convertFrom($source, PersistentResource::class));
    }

    #[Test]
    public function convertFromReturnsTrueIfSourceIsAnArrayWithDataAndFilename()
    {
        $source = [
            'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
            'filename' => 'test.png'
        ];

        $this->mockResourceManager->expects($this->once())->method('importResourceFromContent')->willReturn((new PersistentResource()));

        $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
    }

    #[Test]
    public function convertFromReturnsNullIfNoFileWasUploadedAndEmptyHashIsSet()
    {
        $source = ['error' => \UPLOAD_ERR_NO_FILE, 'hash' => ''];
        self::assertNull($this->resourceTypeConverter->convertFrom($source, PersistentResource::class));
    }

    #[Test]
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
        $this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('79ecda60-1a27-69ca-17bf-a5d9e80e6c39', PersistentResource::class)->willReturn(($expectedResource));

        $actualResource = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);

        self::assertInstanceOf(PersistentResource::class, $actualResource);
        self::assertSame($expectedResource, $actualResource);
    }

    #[Test]
    public function convertFromReturnsNullIfSpecifiedResourceCantBeFound()
    {
        $source = [
            'error' => \UPLOAD_ERR_NO_FILE,
            'originallySubmittedResource' => [
                '__identity' => '79ecda60-1a27-69ca-17bf-a5d9e80e6c39'
            ]
        ];

        $this->inject($this->resourceTypeConverter, 'persistenceManager', $this->mockPersistenceManager);
        $this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('79ecda60-1a27-69ca-17bf-a5d9e80e6c39', PersistentResource::class)->willReturn((null));

        $actualResource = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);

        self::assertNull($actualResource);
    }

    #[Test]
    public function convertFromReturnsAnErrorIfFileUploadFailed()
    {
        $source = [
            'error' => \UPLOAD_ERR_PARTIAL
        ];

        $actualResult = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
        self::assertInstanceOf(FlowError\Error::class, $actualResult);
    }

    #[Test]
    public function convertFromAddsSystemLogEntryIfFileUploadFailedDueToAServerError()
    {
        $source = [
            'error' => \UPLOAD_ERR_CANT_WRITE
        ];

        $mockSystemLogger = $this->createMock(LoggerInterface::class);
        $mockSystemLogger->expects($this->once())->method('error');
        $this->inject($this->resourceTypeConverter, 'logger', $mockSystemLogger);

        $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
    }

    #[Test]
    public function convertFromImportsResourceIfFileUploadSucceeded()
    {
        $source = [
            'tmp_name' => 'SomeFilename',
            'error' => \UPLOAD_ERR_OK
        ];
        $mockResource = $this->createStub(PersistentResource::class);
        $this->mockResourceManager->expects($this->once())->method('importUploadedResource')->with($source)->willReturn(($mockResource));

        $actualResult = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
        self::assertSame($mockResource, $actualResult);
    }

    #[Test]
    public function convertFromReturnsAnErrorIfTheUploadedFileCantBeImported()
    {
        $this->inject($this->resourceTypeConverter, 'logger', $this->createStub(LoggerInterface::class));

        $source = [
            'tmp_name' => 'SomeFilename',
            'error' => \UPLOAD_ERR_OK
        ];
        $this->mockResourceManager->expects($this->once())->method('importUploadedResource')->with($source)->willThrowException(new Exception());

        $actualResult = $this->resourceTypeConverter->convertFrom($source, PersistentResource::class);
        self::assertInstanceOf(FlowError\Error::class, $actualResult);
    }
}
