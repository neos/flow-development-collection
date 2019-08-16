<?php
namespace Neos\Flow\Log\Tests\Unit\Backend;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Log\Exception\CouldNotOpenResourceException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use Neos\Flow\Log\Backend\FileBackend;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the File Backend
 */
class FileBackendTest extends UnitTestCase
{
    /**
     */
    protected function setUp(): void
    {
        vfsStream::setup('testDirectory');
    }

    /**
     * @test
     */
    public function theLogFileIsOpenedWithOpen()
    {
        $logFileUrl = vfsStream::url('testDirectory') . '/test.log';
        $backend = new FileBackend(['logFileUrl' => $logFileUrl]);
        $backend->open();
        self::assertTrue(vfsStreamWrapper::getRoot()->hasChild('test.log'));
    }

    /**
     * @test
     */
    public function openDoesNotCreateParentDirectoriesByDefault()
    {
        $this->expectException(CouldNotOpenResourceException::class);
        $logFileUrl = vfsStream::url('testDirectory') . '/foo/test.log';
        $backend = new FileBackend(['logFileUrl' => $logFileUrl]);
        $backend->open();
    }

    /**
     * @test
     */
    public function openCreatesParentDirectoriesIfTheOptionSaysSo()
    {
        $logFileUrl = vfsStream::url('testDirectory') . '/foo/test.log';
        $backend = new FileBackend(['logFileUrl' => $logFileUrl, 'createParentDirectories' => true]);
        $backend->open();
        self::assertTrue(vfsStreamWrapper::getRoot()->hasChild('foo'));
    }

    /**
     * @test
     */
    public function appendRendersALogEntryAndAppendsItToTheLogfile()
    {
        $logFileUrl = vfsStream::url('testDirectory') . '/test.log';
        $backend = new FileBackend(['logFileUrl' => $logFileUrl]);
        $backend->open();

        $backend->append('foo');

        $pidOffset = function_exists('posix_getpid') ? 10 : 0;
        self::assertSame(53 + $pidOffset + strlen(PHP_EOL), vfsStreamWrapper::getRoot()->getChild('test.log')->size());
    }

    /**
     * @test
     */
    public function appendRendersALogEntryWithRemoteIpAddressAndAppendsItToTheLogfile()
    {
        $logFileUrl = vfsStream::url('testDirectory') . '/test.log';
        $backend = new FileBackend(['logFileUrl' => $logFileUrl]);
        $backend->setLogIpAddress(true);
        $backend->open();

        $backend->append('foo');

        $pidOffset = function_exists('posix_getpid') ? 10 : 0;
        self::assertSame(68 + $pidOffset + strlen(PHP_EOL), vfsStreamWrapper::getRoot()->getChild('test.log')->size());
    }

    /**
     * @test
     */
    public function appendIgnoresMessagesAboveTheSeverityThreshold()
    {
        $logFileUrl = vfsStream::url('testDirectory') . '/test.log';
        $backend = new FileBackend(['logFileUrl' => $logFileUrl]);
        $backend->setSeverityThreshold(LOG_EMERG);
        $backend->open();

        $backend->append('foo', LOG_INFO);

        self::assertSame(0, vfsStreamWrapper::getRoot()->getChild('test.log')->size());
    }

    /**
     * @test
     */
    public function logFileIsRotatedIfMaximumSizeIsExceeded()
    {
        $logFileUrl = vfsStream::url('testDirectory') . '/test.log';
        file_put_contents($logFileUrl, 'twentybytesofcontent');

        /** @var FileBackend $backend */
        $backend = $this->getAccessibleMock(FileBackend::class, ['dummy'], [['logFileUrl' => $logFileUrl]]);
        $backend->_set('maximumLogFileSize', 10);
        $backend->setLogFilesToKeep(1);
        $backend->open();

        self::assertTrue(vfsStreamWrapper::getRoot()->hasChild('test.log'));
        self::assertSame('', file_get_contents($logFileUrl));
        self::assertTrue(vfsStreamWrapper::getRoot()->hasChild('test.log.1'));
    }
}
