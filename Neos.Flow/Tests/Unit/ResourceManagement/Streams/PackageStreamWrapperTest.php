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
use Neos\Flow\ResourceManagement\Streams\PackageStreamWrapper;
use org\bovigo\vfs\vfsStream;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Tests for the PackageStreamWrapper class
 */
class PackageStreamWrapperTest extends UnitTestCase
{
    /**
     * @var PackageStreamWrapper
     */
    protected $packageStreamWrapper;

    /**
     * @var PackageManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPackageManager;

    protected function setUp(): void
    {
        vfsStream::setup('Foo');

        $this->packageStreamWrapper = new PackageStreamWrapper();

        $this->mockPackageManager = $this->createMock(PackageManager::class);
        $this->inject($this->packageStreamWrapper, 'packageManager', $this->mockPackageManager);
    }

    /**
     * @test
     */
    public function openThrowsExceptionForInvalidScheme()
    {
        $this->expectException(\InvalidArgumentException::class);
        $openedPathAndFilename = '';
        $this->packageStreamWrapper->open('invalid-scheme://foo/bar', 'r', 0, $openedPathAndFilename);
    }

    public function providePathesWithForbiddenUpwardsTraversel(): array
    {
        return [
            ['package://Some.Package/../bar'],
            ['package://Some.Package/bar/..'],
            ['package://Some.Package/foo/../bar']
        ];
    }

    /**
     * @test
     * @dataProvider providePathesWithForbiddenUpwardsTraversel
     */
    public function openThrowsExceptionForPathesThatTryToTraverseUpwards(string $forbiddenPath)
    {
        $this->expectException(\InvalidArgumentException::class);
        $openedPathAndFilename = '';
        $this->packageStreamWrapper->open($forbiddenPath, 'r', 0, $openedPathAndFilename);
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
        $this->packageStreamWrapper->open('package://' . $packageKey . '/Some/Path', 'r', 0, $openedPathAndFilename);
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
        $mockPackage->expects(self::any())->method('getPackagePath')->will(self::returnValue('vfs://Foo'));
        $this->mockPackageManager->expects(self::once())->method('getPackage')->with($packageKey)->will(self::returnValue($mockPackage));

        $openedPathAndFilename = '';
        self::assertTrue($this->packageStreamWrapper->open('package://' . $packageKey . '/Some/Path', 'r', 0, $openedPathAndFilename));
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
        $mockPackage->expects(self::any())->method('getPackagePath')->will(self::returnValue('vfs://Foo'));
        $this->mockPackageManager->expects(self::once())->method('getPackage')->with($packageKey)->will(self::returnValue($mockPackage));

        $openedPathAndFilename = '';
        self::assertTrue($this->packageStreamWrapper->open('package://' . $packageKey . '/Some/Path', 'r', 0, $openedPathAndFilename));
        self::assertSame($openedPathAndFilename, 'vfs://Foo/Some/Path');
    }
}
