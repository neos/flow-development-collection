<?php
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

use Neos\Flow\Http\BaseUriProvider;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\ResourceManagement\Collection;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\Storage\PackageStorage;
use Neos\Flow\ResourceManagement\Target\FileSystemTarget;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for the FileSystemTarget class
 */
class FileSystemTargetTest extends UnitTestCase
{
    /**
     * @var FileSystemTarget
     */
    protected $fileSystemTarget;

    /**
     * @var BaseUriProvider|\PHPUnit\Framework\MockObject\MockObject
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
        $this->mockBaseUriProvider->expects(self::any())->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willReturn($uri);
    }

    /**
     * @test
     */
    public function getNameReturnsTargetName()
    {
        self::assertSame('test', $this->fileSystemTarget->getName());
    }

    /**
     * @return array
     */
    public function getPublicStaticResourceUriDataProvider()
    {
        return [
            ['baseUri' => 'http://localhost/', 'relativePathAndFilename' => 'SomeFilename.jpg', 'expectedResult' => 'http://localhost/SomeFilename.jpg'],
            ['baseUri' => 'http://localhost/', 'relativePathAndFilename' => 'some/path/SomeFilename.jpg', 'expectedResult' => 'http://localhost/some/path/SomeFilename.jpg'],
            ['baseUri' => '/absolute/without/protocol/', 'relativePathAndFilename' => 'some/path/SomeFilename.jpg', 'expectedResult' => '/absolute/without/protocol/some/path/SomeFilename.jpg'],
            ['baseUri' => '', 'relativePathAndFilename' => 'some/path/SomeFilename.jpg', 'expectedResult' => 'http://detected/base/uri/some/path/SomeFilename.jpg'],
            ['baseUri' => 'relative/', 'relativePathAndFilename' => 'some/pa th/Some Filename.jpg', 'expectedResult' => 'http://detected/base/uri/relative/some/pa%20th/Some%20Filename.jpg'],
        ];
    }

    /**
     * @test
     * @dataProvider getPublicStaticResourceUriDataProvider
     * @param string $baseUri
     * @param string $relativePathAndFilename
     * @param string $expectedResult
     */
    public function getPublicStaticResourceUriTests($baseUri, $relativePathAndFilename, $expectedResult)
    {
        $this->provideBaseUri(new Uri('http://detected/base/uri/'));
        $this->inject($this->fileSystemTarget, 'baseUri', $baseUri);
        self::assertSame($expectedResult, $this->fileSystemTarget->getPublicStaticResourceUri($relativePathAndFilename));
    }

    /**
     * @test
     */
    public function getPublicStaticResourceUriFallsBackToConfiguredHttpBaseUri()
    {
        $this->provideBaseUri(new Uri('http://configured/http/base/uri/'));
        self::assertStringStartsWith('http://configured/http/base/uri/', $this->fileSystemTarget->getPublicStaticResourceUri('some/path/SomeFilename.jpg'));
    }

    /**
     * @test
     */
    public function getPublicStaticResourceUriThrowsExceptionIfBaseUriCantBeResolved()
    {
        $this->expectException(\Neos\Flow\Http\Exception::class);
        $this->mockBaseUriProvider->expects(self::any())->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willThrowException(new \Neos\Flow\Http\Exception('Test mock exception'));

        $this->fileSystemTarget->getPublicStaticResourceUri('some/path/SomeFilename.jpg');
    }

    /**
     * @return array
     */
    public function getPublicPersistentResourceUriDataProvider()
    {
        return [
            ['baseUri' => 'http://localhost/', 'relativePublicationPath' => 'some/path/', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/some/path/SomeFilename.jpg'],
            ['baseUri' => 'http://localhost/', 'relativePublicationPath' => '', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/8/6/e/f/86eff8eb789b097ddca83f2c9c4617ed23605105/SomeFilename.jpg'],
            ['baseUri' => 'http://localhost/', 'relativePublicationPath' => '', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/8/6/e/f/86eff8eb789b097ddca83f2c9c4617ed23605105/SomeFilename.jpg'],
            ['baseUri' => 'http://localhost/', 'relativePublicationPath' => 'so me/path/', 'filename' => 'Some Filename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://localhost/so%20me/path/Some%20Filename.jpg'],
            ['baseUri' => '/absolute/uri/without/protocol/', 'relativePublicationPath' => '', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => '/absolute/uri/without/protocol/8/6/e/f/86eff8eb789b097ddca83f2c9c4617ed23605105/SomeFilename.jpg'],
            ['baseUri' => '', 'relativePublicationPath' => '', 'filename' => 'SomeFilename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://detected/base/uri/8/6/e/f/86eff8eb789b097ddca83f2c9c4617ed23605105/SomeFilename.jpg'],
            ['baseUri' => 'relative/', 'relativePublicationPath' => 'so me/path/', 'filename' => 'Some Filename.jpg', 'sha1' => '86eff8eb789b097ddca83f2c9c4617ed23605105', 'expectedResult' => 'http://detected/base/uri/relative/so%20me/path/Some%20Filename.jpg'],
        ];
    }

    /**
     * @test
     * @dataProvider getPublicPersistentResourceUriDataProvider
     * @param string $baseUri
     * @param string $relativePublicationPath
     * @param string $filename
     * @param string $sha1
     * @param string $expectedResult
     */
    public function getPublicPersistentResourceUriTests($baseUri, $relativePublicationPath, $filename, $sha1, $expectedResult)
    {
        $this->provideBaseUri(new Uri('http://detected/base/uri/'));
        $this->inject($this->fileSystemTarget, 'baseUri', $baseUri);
        /** @var PersistentResource|\PHPUnit\Framework\MockObject\MockObject $mockResource */
        $mockResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $mockResource->expects(self::any())->method('getRelativePublicationPath')->will(self::returnValue($relativePublicationPath));
        $mockResource->expects(self::any())->method('getFilename')->will(self::returnValue($filename));
        $mockResource->expects(self::any())->method('getSha1')->will(self::returnValue($sha1));

        self::assertSame($expectedResult, $this->fileSystemTarget->getPublicPersistentResourceUri($mockResource));
    }

    /**
     * @test
     */
    public function getPublicPersistentResourceUriFallsBackToConfiguredHttpBaseUri()
    {
        $this->provideBaseUri(new Uri('http://configured/http/base/uri/'));

        /** @var PersistentResource|\PHPUnit\Framework\MockObject\MockObject $mockResource */
        $mockResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();

        self::assertStringStartsWith('http://configured/http/base/uri/', $this->fileSystemTarget->getPublicPersistentResourceUri($mockResource));
    }

    /**
     * @test
     */
    public function getPublicPersistentResourceUriThrowsExceptionIfBaseUriCantBeResolved()
    {
        $this->expectException(\Neos\Flow\Http\Exception::class);
        $this->mockBaseUriProvider->expects(self::any())->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willThrowException(new \Neos\Flow\Http\Exception('Test mock exception'));

        /** @var PersistentResource|\PHPUnit\Framework\MockObject\MockObject $mockResource */
        $mockResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();

        $this->fileSystemTarget->getPublicStaticResourceUri($mockResource);
    }

    /**
     * @test
     */
    public function getWorksWithPackageStorage()
    {
        vfsStream::setup('Test');
        mkdir('vfs://Test/Configuration');
        $packageManager = new PackageManager('vfs://Test/Configuration/PackageStates.php', 'vfs://Test/Packages/');

        $packageManager->createPackage("Some.Testing.Package", [], 'vfs://Test/Packages/Application');

        $packageStorage = new PackageStorage('testStorage');
        $packageStorage->initializeObject(ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);

        $mockSystemLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->inject($packageStorage, 'packageManager', $packageManager);

        $oneResourcePublished = false;

        $_publicationCallback = function ($i, $o) use (&$oneResourcePublished) {
            $oneResourcePublished = true;
        };

        $staticCollection = new Collection('testStaticCollection', $packageStorage, $this->fileSystemTarget, ['*']);

        $fileSystemTarget = new FileSystemTarget('test', ['path' => 'vfs://Test/Publish']);
        $fileSystemTarget->initializeObject(ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);
        $fileSystemTarget->injectLogger($mockSystemLogger);
        $fileSystemTarget->publishCollection($staticCollection, $_publicationCallback);

        self::assertTrue($oneResourcePublished);
    }
}
