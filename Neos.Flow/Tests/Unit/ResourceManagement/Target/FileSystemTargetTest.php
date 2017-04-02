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

use Neos\Flow\Cli\CommandRequestHandler;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\ResourceManagement\Collection;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\Storage\PackageStorage;
use Neos\Flow\ResourceManagement\Target\FileSystemTarget;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

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
     * @var Bootstrap|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockBootstrap;

    /**
     * @var HttpRequestHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRequestHandler;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    public function setUp()
    {
        $this->fileSystemTarget = new FileSystemTarget('test');

        $this->mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();

        $this->mockRequestHandler = $this->createMock(HttpRequestHandlerInterface::class);

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects($this->any())->method('getBaseUri')->will($this->returnValue(new Uri('http://detected/base/uri/')));
        $this->mockRequestHandler->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

        $this->mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($this->mockRequestHandler));
        $this->inject($this->fileSystemTarget, 'bootstrap', $this->mockBootstrap);
    }

    /**
     * @test
     */
    public function getNameReturnsTargetName()
    {
        $this->assertSame('test', $this->fileSystemTarget->getName());
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
        $this->inject($this->fileSystemTarget, 'baseUri', $baseUri);
        $this->assertSame($expectedResult, $this->fileSystemTarget->getPublicStaticResourceUri($relativePathAndFilename));
    }

    /**
     * @test
     */
    public function getPublicStaticResourceUriFallsBackToConfiguredHttpBaseUri()
    {
        $mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $mockCommandRequestHandler = $this->getMockBuilder(CommandRequestHandler::class)->disableOriginalConstructor()->getMock();
        $mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockCommandRequestHandler));
        $this->inject($this->fileSystemTarget, 'bootstrap', $mockBootstrap);
        $this->inject($this->fileSystemTarget, 'httpBaseUri', 'http://configured/http/base/uri/');

        $this->assertStringStartsWith('http://configured/http/base/uri/', $this->fileSystemTarget->getPublicStaticResourceUri('some/path/SomeFilename.jpg'));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\ResourceManagement\Target\Exception
     */
    public function getPublicStaticResourceUriThrowsExceptionIfBaseUriCantBeResolved()
    {
        $mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $mockCommandRequestHandler = $this->getMockBuilder(CommandRequestHandler::class)->disableOriginalConstructor()->getMock();
        $mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockCommandRequestHandler));
        $this->inject($this->fileSystemTarget, 'bootstrap', $mockBootstrap);

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
        $this->inject($this->fileSystemTarget, 'baseUri', $baseUri);
        /** @var PersistentResource|\PHPUnit_Framework_MockObject_MockObject $mockResource */
        $mockResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $mockResource->expects($this->any())->method('getRelativePublicationPath')->will($this->returnValue($relativePublicationPath));
        $mockResource->expects($this->any())->method('getFilename')->will($this->returnValue($filename));
        $mockResource->expects($this->any())->method('getSha1')->will($this->returnValue($sha1));

        $this->assertSame($expectedResult, $this->fileSystemTarget->getPublicPersistentResourceUri($mockResource));
    }

    /**
     * @test
     */
    public function getPublicPersistentResourceUriFallsBackToConfiguredHttpBaseUri()
    {
        $mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $mockCommandRequestHandler = $this->getMockBuilder(CommandRequestHandler::class)->disableOriginalConstructor()->getMock();
        $mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockCommandRequestHandler));
        $this->inject($this->fileSystemTarget, 'bootstrap', $mockBootstrap);
        $this->inject($this->fileSystemTarget, 'httpBaseUri', 'http://configured/http/base/uri/');

        /** @var PersistentResource|\PHPUnit_Framework_MockObject_MockObject $mockResource */
        $mockResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();

        $this->assertStringStartsWith('http://configured/http/base/uri/', $this->fileSystemTarget->getPublicPersistentResourceUri($mockResource));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\ResourceManagement\Target\Exception
     */
    public function getPublicPersistentResourceUriThrowsExceptionIfBaseUriCantBeResolved()
    {
        $mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $mockCommandRequestHandler = $this->getMockBuilder(CommandRequestHandler::class)->disableOriginalConstructor()->getMock();
        $mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockCommandRequestHandler));
        $this->inject($this->fileSystemTarget, 'bootstrap', $mockBootstrap);

        /** @var PersistentResource|\PHPUnit_Framework_MockObject_MockObject $mockResource */
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
        $packageManager = new PackageManager('vfs://Test/Configuration/PackageStates.php');
        $packageManager->createPackage("Neos.Flow");
        $packageManager->activatePackage("Neos.Flow");

        $packageStorage = new PackageStorage('testStorage');

        $mockSystemLogger = $this->getMockBuilder(SystemLoggerInterface::class)->getMock();

        $this->inject($packageStorage, 'packageManager', $packageManager);

        $oneResourcePublished = false;

        $_publicationCallback = function ($i, $o) use (&$oneResourcePublished) {
            $oneResourcePublished = true;
        };

        $staticCollection = new Collection('testStaticCollection', $packageStorage, $this->fileSystemTarget, ['*']);

        $fileSystemTarget = new FileSystemTarget('test', ['path' => 'vfs://Publish']);
        $this->inject($fileSystemTarget, 'systemLogger', $mockSystemLogger);
        $fileSystemTarget->publishCollection($staticCollection, $_publicationCallback);

        $this->assertTrue($oneResourcePublished);
    }
}
