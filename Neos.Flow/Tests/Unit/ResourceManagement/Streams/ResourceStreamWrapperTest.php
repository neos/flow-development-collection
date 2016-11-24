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

use org\bovigo\vfs\vfsStream;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Package\PackageManagerInterface;
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
     * @var PackageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPackageManager;

    /**
     * @var ResourceManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockResourceManager;

    public function setUp()
    {
        vfsStream::setup('Foo');

        $this->resourceStreamWrapper = new ResourceStreamWrapper();

        $this->mockPackageManager = $this->createMock(PackageManagerInterface::class);
        $this->inject($this->resourceStreamWrapper, 'packageManager', $this->mockPackageManager);

        $this->mockResourceManager = $this->getMockBuilder(ResourceManager::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->resourceStreamWrapper, 'resourceManager', $this->mockResourceManager);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function openThrowsExceptionForInvalidScheme()
    {
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
        $this->mockResourceManager->expects($this->once())->method('getResourceBySha1')->with($sha1Hash)->will($this->returnValue($mockResource));
        $this->mockResourceManager->expects($this->once())->method('getStreamByResource')->with($mockResource)->will($this->returnValue($tempFile));

        $openedPathAndFilename = '';
        $this->assertTrue($this->resourceStreamWrapper->open('resource://' . $sha1Hash, 'r', 0, $openedPathAndFilename));
        $this->assertAttributeSame($tempFile, 'handle', $this->resourceStreamWrapper);
    }

    /**
     * @test
     */
    public function openResolvesAnUpperCaseSha1HashUsingTheResourceManager()
    {
        $sha1Hash = '68AC906495480A3404BEEE4874ED853A037A7A8F';

        $tempFile = tmpfile();

        $mockResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $this->mockResourceManager->expects($this->once())->method('getResourceBySha1')->with($sha1Hash)->will($this->returnValue($mockResource));
        $this->mockResourceManager->expects($this->once())->method('getStreamByResource')->with($mockResource)->will($this->returnValue($tempFile));

        $openedPathAndFilename = '';
        $this->assertTrue($this->resourceStreamWrapper->open('resource://' . $sha1Hash, 'r', 0, $openedPathAndFilename));
        $this->assertAttributeSame($tempFile, 'handle', $this->resourceStreamWrapper);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\ResourceManagement\Exception
     */
    public function openThrowsExceptionForNonExistingPackages()
    {
        $packageKey = 'Non.Existing.Package';

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

        $this->mockPackageManager->expects($this->once())->method('isPackageAvailable')->with($packageKey)->will($this->returnValue(true));

        $mockPackage = $this->createMock(PackageInterface::class);
        $mockPackage->expects($this->any())->method('getResourcesPath')->will($this->returnValue('vfs://Foo'));
        $this->mockPackageManager->expects($this->once())->method('getPackage')->with($packageKey)->will($this->returnValue($mockPackage));

        $openedPathAndFilename = '';
        $this->assertTrue($this->resourceStreamWrapper->open('resource://' . $packageKey . '/Some/Path', 'r', 0, $openedPathAndFilename));
        $this->assertSame($openedPathAndFilename, 'vfs://Foo/Some/Path');
    }

    /**
     * @test
     */
    public function openResolves40CharacterLongPackageKeysUsingThePackageManager()
    {
        $packageKey = 'Some.PackageKey.Containing.40.Characters';
        mkdir('vfs://Foo/Some/');
        file_put_contents('vfs://Foo/Some/Path', 'fixture');

        $this->mockPackageManager->expects($this->once())->method('isPackageAvailable')->with($packageKey)->will($this->returnValue(true));

        $mockPackage = $this->createMock(PackageInterface::class);
        $mockPackage->expects($this->any())->method('getResourcesPath')->will($this->returnValue('vfs://Foo'));
        $this->mockPackageManager->expects($this->once())->method('getPackage')->with($packageKey)->will($this->returnValue($mockPackage));

        $openedPathAndFilename = '';
        $this->assertTrue($this->resourceStreamWrapper->open('resource://' . $packageKey . '/Some/Path', 'r', 0, $openedPathAndFilename));
        $this->assertSame($openedPathAndFilename, 'vfs://Foo/Some/Path');
    }
}
