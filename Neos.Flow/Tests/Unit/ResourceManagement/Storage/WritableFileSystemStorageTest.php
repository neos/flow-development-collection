<?php
namespace Neos\Flow\Tests\Unit\ResourceManagement\Storage;

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
use org\bovigo\vfs\vfsStreamDirectory;
use Neos\Flow\ResourceManagement\Storage\WritableFileSystemStorage;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Files;

/**
 * Test case for the WritableFileSystemStorage class
 */
class WritableFileSystemStorageTest extends UnitTestCase
{
    /**
     * @var WritableFileSystemStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $writableFileSystemStorage;

    /**
     * @var vfsStreamDirectory
     */
    protected $mockDirectory;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockEnvironment;

    public function setUp()
    {
        $this->mockDirectory = vfsStream::setup('WritableFileSystemStorageTest');

        $this->writableFileSystemStorage = $this->getAccessibleMock(WritableFileSystemStorage::class, null, ['testStorage', ['path' => 'vfs://WritableFileSystemStorageTest/']]);

        $this->mockEnvironment = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $this->mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://WritableFileSystemStorageTest/'));
        $this->inject($this->writableFileSystemStorage, 'environment', $this->mockEnvironment);
    }

    /**
     * @test
     */
    public function importTemporaryFileFixesPermissionsForTemporaryFile()
    {
        $mockTempFile = vfsStream::newFile('SomeTemporaryFile', 0333)
            ->withContent('fixture')
            ->at($this->mockDirectory);
        $this->writableFileSystemStorage->_call('importTemporaryFile', $mockTempFile->url(), 'default');

        // dummy assertion to suppress PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function importTemporaryFileSkipsFilesThatAlreadyExist()
    {
        $mockTempFile = vfsStream::newFile('SomeTemporaryFile', 0333)
            ->withContent('fixture')
            ->at($this->mockDirectory);

        $finalTargetPathAndFilename = $this->writableFileSystemStorage->_call('getStoragePathAndFilenameByHash', sha1('fixture'));
        Files::createDirectoryRecursively(dirname($finalTargetPathAndFilename));
        file_put_contents($finalTargetPathAndFilename, 'existing file');

        $this->writableFileSystemStorage->_call('importTemporaryFile', $mockTempFile->url(), 'default');

        $this->assertSame('existing file', file_get_contents($finalTargetPathAndFilename));
    }
}
