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

use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Utility\ObjectAccess;
use org\bovigo\vfs\vfsStream;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\Streams\ResourceStreamWrapper;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Tests for the ResourceStreamWrapper class
 */
class ResourceStreamWrapperTest extends UnitTestCase
{
    /**
     * @var ResourceStreamWrapper
     */
    protected $resourceStreamWrapper;

    /**
     * @var PackageManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPackageManager;

    /**
     * @var ResourceManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockResourceManager;

    protected function setUp(): void
    {
        vfsStream::setup('Foo');

        $this->resourceStreamWrapper = new ResourceStreamWrapper();

        $this->mockPackageManager = $this->createMock(PackageManager::class);
        $this->inject($this->resourceStreamWrapper, 'packageManager', $this->mockPackageManager);

        $this->mockResourceManager = $this->getMockBuilder(ResourceManager::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->resourceStreamWrapper, 'resourceManager', $this->mockResourceManager);
    }

    /**
     * @test
     */
    public function openThrowsExceptionForInvalidScheme()
    {
        $this->expectException(\InvalidArgumentException::class);
        $openedPathAndFilename = '';
        $this->resourceStreamWrapper->open('invalid-scheme://foo/bar', 'r', 0, $openedPathAndFilename);
    }

    /**
     * @test
     */
    public function openResolvesALowerCaseSha1HashUsingTheResourceManager()
    {
        $sha1Hash = '68ac906495480a3404beee4874ed853a037a7a8f';

        $tempFile = tmpfile();

        $mockResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $this->mockResourceManager->expects(self::once())->method('getResourceBySha1')->with($sha1Hash)->will(self::returnValue($mockResource));
        $this->mockResourceManager->expects(self::once())->method('getStreamByResource')->with($mockResource)->will(self::returnValue($tempFile));

        $openedPathAndFilename = '';
        self::assertTrue($this->resourceStreamWrapper->open('resource://' . $sha1Hash, 'r', 0, $openedPathAndFilename));
        self::assertSame($tempFile, ObjectAccess::getProperty($this->resourceStreamWrapper, 'handle', true));
    }

    /**
     * @test
     */
    public function openResolvesAnUpperCaseSha1HashUsingTheResourceManager()
    {
        $sha1Hash = '68AC906495480A3404BEEE4874ED853A037A7A8F';

        $tempFile = tmpfile();

        $mockResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $this->mockResourceManager->expects(self::once())->method('getResourceBySha1')->with($sha1Hash)->will(self::returnValue($mockResource));
        $this->mockResourceManager->expects(self::once())->method('getStreamByResource')->with($mockResource)->will(self::returnValue($tempFile));

        $openedPathAndFilename = '';
        self::assertTrue($this->resourceStreamWrapper->open('resource://' . $sha1Hash, 'r', 0, $openedPathAndFilename));
        self::assertSame($tempFile, ObjectAccess::getProperty($this->resourceStreamWrapper, 'handle', true));
    }

    /**
     * @test
     */
    public function openThrowsExceptionForNonExistingPackages()
    {
        $this->expectException(Exception::class);
        $packageKey = 'Non.Existing.Package';
        $this->mockPackageManager->expects(self::once())->method('getPackage')->willThrowException(new \Neos\Flow\Package\Exception\UnknownPackageException('Test exception'));

        $openedPathAndFilename = '';
        $this->resourceStreamWrapper->open('resource://' . $packageKey . '/Some/Path', 'r', 0, $openedPathAndFilename);
    }

    /**
     * @test
     */
    public function openResolvesPackageKeysUsingThePackageManager()
    {
        $packageKey = 'Some.Package';
        mkdir('vfs://Foo/Some/');
        file_put_contents('vfs://Foo/Some/Path', 'fixture');

        $mockPackage = $this->createMock(FlowPackageInterface::class);
        $mockPackage->expects(self::any())->method('getResourcesPath')->will(self::returnValue('vfs://Foo'));
        $this->mockPackageManager->expects(self::once())->method('getPackage')->with($packageKey)->will(self::returnValue($mockPackage));

        $openedPathAndFilename = '';
        self::assertTrue($this->resourceStreamWrapper->open('resource://' . $packageKey . '/Some/Path', 'r', 0, $openedPathAndFilename));
        self::assertSame($openedPathAndFilename, 'vfs://Foo/Some/Path');
    }

    /**
     * @test
     */
    public function openResolves40CharacterLongPackageKeysUsingThePackageManager()
    {
        $packageKey = 'Some.PackageKey.Containing.40.Characters';
        mkdir('vfs://Foo/Some/');
        file_put_contents('vfs://Foo/Some/Path', 'fixture');

        $mockPackage = $this->createMock(FlowPackageInterface::class);
        $mockPackage->expects(self::any())->method('getResourcesPath')->will(self::returnValue('vfs://Foo'));
        $this->mockPackageManager->expects(self::once())->method('getPackage')->with($packageKey)->will(self::returnValue($mockPackage));

        $openedPathAndFilename = '';
        self::assertTrue($this->resourceStreamWrapper->open('resource://' . $packageKey . '/Some/Path', 'r', 0, $openedPathAndFilename));
        self::assertSame($openedPathAndFilename, 'vfs://Foo/Some/Path');
    }
}
