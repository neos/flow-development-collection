<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\ResourceManagement\Streams;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Http\Exception;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\ResourceManagement\Collection;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\Storage\PackageStorage;
use Neos\Flow\ResourceManagement\Target\FileSystemTarget;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for the FileSystemTarget class
 */
final class FileSystemTargetTest extends UnitTestCase
{
    /**
     * @var FileSystemTarget
     */
    protected $fileSystemTarget;

    /**
     * @var BaseUriProvider|MockObject
     */
    protected $mockBaseUriProvider;

    protected function setUp(): void
    {
        $this->fileSystemTarget = new FileSystemTarget('test');

        $this->mockBaseUriProvider = $this->createMock(BaseUriProvider::class);

        $this->inject($this->fileSystemTarget, 'baseUriProvider', $this->mockBaseUriProvider);
    }

    protected function provideBaseUri(UriInterface $uri)
    {
        $this->mockBaseUriProvider->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willReturn($uri);
    }

    #[Test]
    public function getNameReturnsTargetName()
    {
        self::assertSame('test', $this->fileSystemTarget->getName());
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function getPublicStaticResourceUriDataProvider(): \Iterator
    {
        yield ['baseUri' => 'http://localhost/', 'relativePathAndFilename' => 'SomeFilename.jpg', 'expectedResult' => 'http://localhost/SomeFilename.jpg'];
        yield ['baseUri' => 'http://localhost/', 'relativePathAndFilename' => 'some/path/SomeFilename.jpg', 'expectedResult' => 'http://localhost/some/path/SomeFilename.jpg'];
        yield ['baseUri' => '/absolute/without/protocol/', 'relativePathAndFilename' => 'some/path/SomeFilename.jpg', 'expectedResult' => '/absolute/without/protocol/some/path/SomeFilename.jpg'];
        yield ['baseUri' => '', 'relativePathAndFilename' => 'some/path/SomeFilename.jpg', 'expectedResult' => 'http://detected/base/uri/some/path/SomeFilename.jpg'];
        yield ['baseUri' => 'relative/', 'relativePathAndFilename' => 'some/pa th/Some Filename.jpg', 'expectedResult' => 'http://detected/base/uri/relative/some/pa%20th/Some%20Filename.jpg'];
    }

    /**
     * @param string $baseUri
     * @param string $relativePathAndFilename
     * @param string $expectedResult
     */
    #[DataProvider('getPublicStaticResourceUriDataProvider')]
    #[Test]
    public function getPublicStaticResourceUriTests($baseUri, $relativePathAndFilename, $expectedResult)
    {
        $this->provideBaseUri(new Uri('http://detected/base/uri/'));
        $this->inject($this->fileSystemTarget, 'baseUri', $baseUri);
        self::assertSame($expectedResult, $this->fileSystemTarget->getPublicStaticResourceUri($relativePathAndFilename));
    }

    #[Test]
    public function getPublicStaticResourceUriFallsBackToConfiguredHttpBaseUri()
    {
        $this->provideBaseUri(new Uri('http://configured/http/base/uri/'));
        self::assertStringStartsWith('http://configured/http/base/uri/', $this->fileSystemTarget->getPublicStaticResourceUri('some/path/SomeFilename.jpg'));
    }

    #[Test]
    public function getPublicStaticResourceUriThrowsExceptionIfBaseUriCantBeResolved()
    {
        $this->expectException(Exception::class);
        $this->mockBaseUriProvider->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willThrowException(new Exception('Test mock exception'));

        $this->fileSystemTarget->getPublicStaticResourceUri('some/path/SomeFilename.jpg');
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function getPublicPersistentResourceUriDataProvider(): \Iterator
    {
        yield ['baseUri' => 'http://localhost/', 'relativePublicationPath' => 'some/path/', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/some/path/SomeFilename.jpg'];
        yield ['baseUri' => 'http://localhost/', 'relativePublicationPath' => '', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/8/6/e/f/86eff8eb789b097ddca83f2c9c4617ed23605105/SomeFilename.jpg'];
        yield ['baseUri' => 'http://localhost/', 'relativePublicationPath' => '', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/8/6/e/f/86eff8eb789b097ddca83f2c9c4617ed23605105/SomeFilename.jpg'];
        yield ['baseUri' => 'http://localhost/', 'relativePublicationPath' => 'so me/path/', 'filename' => 'Some Filename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/so%20me/path/Some%20Filename.jpg'];
        yield ['baseUri' => '/absolute/uri/without/protocol/', 'relativePublicationPath' => '', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => '/absolute/uri/without/protocol/8/6/e/f/86eff8eb789b097ddca83f2c9c4617ed23605105/SomeFilename.jpg'];
        yield ['baseUri' => '', 'relativePublicationPath' => '', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://detected/base/uri/8/6/e/f/86eff8eb789b097ddca83f2c9c4617ed23605105/SomeFilename.jpg'];
        yield ['baseUri' => 'relative/', 'relativePublicationPath' => 'so me/path/', 'filename' => 'Some Filename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://detected/base/uri/relative/so%20me/path/Some%20Filename.jpg'];
    }

    /**
     * @param string $baseUri
     * @param string $relativePublicationPath
     * @param string $filename
     * @param string $sha1
     * @param string $expectedResult
     */
    #[DataProvider('getPublicPersistentResourceUriDataProvider')]
    #[Test]
    public function getPublicPersistentResourceUriTests($baseUri, $relativePublicationPath, $filename, $sha1, $expectedResult)
    {
        $this->provideBaseUri(new Uri('http://detected/base/uri/'));
        $this->inject($this->fileSystemTarget, 'baseUri', $baseUri);
        /** @var PersistentResource|MockObject $mockResource */
        $mockResource = $this->createMock(PersistentResource::class);
        $mockResource->method('getRelativePublicationPath')->willReturn(($relativePublicationPath));
        $mockResource->method('getFilename')->willReturn(($filename));
        $mockResource->method('getSha1')->willReturn(($sha1));

        self::assertSame($expectedResult, $this->fileSystemTarget->getPublicPersistentResourceUri($mockResource));
    }

    #[Test]
    public function getPublicPersistentResourceUriFallsBackToConfiguredHttpBaseUri()
    {
        $this->provideBaseUri(new Uri('http://configured/http/base/uri/'));

        /** @var PersistentResource|MockObject $mockResource */
        $mockResource = $this->createStub(PersistentResource::class);

        self::assertStringStartsWith('http://configured/http/base/uri/', $this->fileSystemTarget->getPublicPersistentResourceUri($mockResource));
    }

    #[Test]
    public function getPublicPersistentResourceUriThrowsExceptionIfBaseUriCantBeResolved()
    {
        $this->expectException(Exception::class);
        $this->mockBaseUriProvider->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willThrowException(new Exception('Test mock exception'));

        /** @var PersistentResource|MockObject $mockResource */
        $mockResource = $this->createStub(PersistentResource::class);

        $this->fileSystemTarget->getPublicStaticResourceUri($mockResource);
    }

    #[Test]
    public function getWorksWithPackageStorage()
    {
        vfsStream::setup('Test');
        mkdir('vfs://Test/Configuration');
        $packageManager = new PackageManager('vfs://Test/Configuration/PackageStates.php', 'vfs://Test/Packages/');

        $packageManager->createPackage("Some.Testing.Package", [], 'vfs://Test/Packages/Application');

        $packageStorage = new PackageStorage('testStorage');
        $packageStorage->initializeObject(ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);

        $mockSystemLogger = $this->createStub(LoggerInterface::class);

        $this->inject($packageStorage, 'packageManager', $packageManager);

        $oneResourcePublished = false;

        $_publicationCallback = function ($i) use (&$oneResourcePublished) {
            $oneResourcePublished = true;
        };

        $staticCollection = new Collection('testStaticCollection', $packageStorage, $this->fileSystemTarget, ['*']);

        $fileSystemTarget = new FileSystemTarget('test', ['path' => 'vfs://Test/Publish']);
        $fileSystemTarget->initializeObject(ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);
        $fileSystemTarget->injectLogger($mockSystemLogger);
        $fileSystemTarget->onPublish($_publicationCallback);
        $fileSystemTarget->publishCollection($staticCollection);

        self::assertTrue($oneResourcePublished);
    }
}
