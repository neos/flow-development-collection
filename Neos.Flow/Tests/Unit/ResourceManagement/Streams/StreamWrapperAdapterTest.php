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

use Neos\Flow\ResourceManagement\Streams\StreamWrapperAdapter;
use Neos\Flow\ResourceManagement\Streams\StreamWrapperInterface;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the StreamWrapperAdapter class
 */
class StreamWrapperAdapterTest extends UnitTestCase
{
    /**
     * @var StreamWrapperAdapter
     */
    protected $streamWrapperAdapter;

    /**
     * @var StreamWrapperInterface
     */
    protected $mockStreamWrapper;


    protected function setUp(): void
    {
        $this->streamWrapperAdapter = $this->getAccessibleMock(StreamWrapperAdapter::class, ['createStreamWrapper']);
        $this->mockStreamWrapper = $this->createMock(StreamWrapperInterface::class);
        $this->streamWrapperAdapter->_set('streamWrapper', $this->mockStreamWrapper);
    }

    /**
     * @test
     */
    public function getRegisteredStreamWrappersReturnsRegisteredStreamWrappers()
    {
        $mockStreamWrapper1ClassName = get_class($this->mockStreamWrapper);
        $mockStreamWrapper2 = $this->createMock(StreamWrapperInterface::class);
        $mockStreamWrapper2ClassName = get_class($mockStreamWrapper2);

        StreamWrapperAdapter::registerStreamWrapper('mockScheme1', $mockStreamWrapper1ClassName);
        StreamWrapperAdapter::registerStreamWrapper('mockScheme2', $mockStreamWrapper2ClassName);

        $registeredStreamWrappers = StreamWrapperAdapter::getRegisteredStreamWrappers();
        self::assertSame($mockStreamWrapper1ClassName, $registeredStreamWrappers['mockScheme1']);
        self::assertSame($mockStreamWrapper2ClassName, $registeredStreamWrappers['mockScheme2']);
    }

    /**
     * @test
     */
    public function dir_closedirTest()
    {
        $this->mockStreamWrapper->expects(self::once())->method('closeDirectory')->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->dir_closedir());
    }

    /**
     * @test
     */
    public function dir_opendirTest()
    {
        $path = 'mockScheme1://foo/bar';
        $options = 123;

        $this->streamWrapperAdapter->expects(self::once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects(self::once())->method('openDirectory')->with($path, $options)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->dir_opendir($path, $options));
    }

    /**
     * @test
     */
    public function dir_readdirTest()
    {
        $this->mockStreamWrapper->expects(self::once())->method('readDirectory')->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->dir_readdir());
    }

    /**
     * @test
     */
    public function dir_rewinddirTest()
    {
        $this->mockStreamWrapper->expects(self::once())->method('rewindDirectory')->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->dir_rewinddir());
    }

    /**
     * @test
     */
    public function mkdirTest()
    {
        $path = 'mockScheme1://foo/bar';
        $mode = '0654';
        $options = STREAM_MKDIR_RECURSIVE;

        $this->streamWrapperAdapter->expects(self::once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects(self::once())->method('makeDirectory')->with($path, $mode, $options)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->mkdir($path, $mode, $options));
    }

    /**
     * @test
     */
    public function renameTest()
    {
        $fromPath = 'mockScheme1://foo/bar';
        $toPath = 'mockScheme1://foo/baz';

        $this->streamWrapperAdapter->expects(self::once())->method('createStreamWrapper')->with($fromPath);
        $this->mockStreamWrapper->expects(self::once())->method('rename')->with($fromPath, $toPath)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->rename($fromPath, $toPath));
    }

    /**
     * @test
     */
    public function rmdirTest()
    {
        $path = 'mockScheme1://foo/bar';
        $options = STREAM_MKDIR_RECURSIVE;

        $this->streamWrapperAdapter->expects(self::once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects(self::once())->method('removeDirectory')->with($path, $options)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->rmdir($path, $options));
    }

    /**
     * @test
     */
    public function stream_castTest()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('stream_cast is not supported in HHVM (see http://docs.hhvm.com/manual/en/streamwrapper.stream-cast.php)');
        }
        $castAs = STREAM_CAST_FOR_SELECT;

        $this->mockStreamWrapper->expects(self::once())->method('cast')->with($castAs)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_cast($castAs));
    }

    /**
     * @test
     */
    public function stream_closeTest()
    {
        $this->mockStreamWrapper->expects(self::once())->method('close');
        $this->streamWrapperAdapter->stream_close();
    }

    /**
     * @test
     */
    public function stream_eofTest()
    {
        $this->mockStreamWrapper->expects(self::once())->method('isAtEof')->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_eof());
    }

    /**
     * @test
     */
    public function stream_flushTest()
    {
        $this->mockStreamWrapper->expects(self::once())->method('flush')->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_flush());
    }

    /**
     * @test
     */
    public function stream_lockTest()
    {
        $operation = LOCK_SH;

        $this->mockStreamWrapper->expects(self::once())->method('lock')->with($operation)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_lock($operation));
    }

    /**
     * @test
     */
    public function stream_unlockTest()
    {
        $operation = LOCK_UN;

        $this->mockStreamWrapper->expects(self::once())->method('unlock')->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_lock($operation));
    }

    /**
     * @test
     */
    public function stream_openTest()
    {
        $path = 'mockScheme1://foo/bar';
        $mode = 'r+';
        $options = STREAM_REPORT_ERRORS;
        $openedPath = '';

        $this->streamWrapperAdapter->expects(self::once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects(self::once())->method('open')->with($path, $mode, $options, $openedPath)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_open($path, $mode, $options, $openedPath));
    }

    /**
     * @test
     */
    public function stream_readTest()
    {
        $count = 123;

        $this->mockStreamWrapper->expects(self::once())->method('read')->with($count)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_read($count));
    }

    /**
     * @test
     */
    public function stream_seekTest()
    {
        $offset = 123;

        $this->mockStreamWrapper->expects(self::once())->method('seek')->with($offset, SEEK_SET)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_seek($offset));
    }

    /**
     * @test
     */
    public function stream_seekTest2()
    {
        $offset = 123;
        $whence = SEEK_END;

        $this->mockStreamWrapper->expects(self::once())->method('seek')->with($offset, $whence)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_seek($offset, $whence));
    }

    /**
     * @test
     */
    public function stream_set_optionTest()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('stream_set_option is not supported in HHVM (see http://docs.hhvm.com/manual/en/streamwrapper.stream-set-option.php)');
        }
        $option = STREAM_OPTION_READ_TIMEOUT;
        $arg1 = 123;
        $arg2 = 123000000;

        $this->mockStreamWrapper->expects(self::once())->method('setOption')->with($option, $arg1, $arg2)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_set_option($option, $arg1, $arg2));
    }

    /**
     * @test
     */
    public function stream_statTest()
    {
        $this->mockStreamWrapper->expects(self::once())->method('resourceStat')->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_stat());
    }

    /**
     * @test
     */
    public function stream_tellTest()
    {
        $this->mockStreamWrapper->expects(self::once())->method('tell')->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_tell());
    }

    /**
     * @test
     */
    public function stream_writeTest()
    {
        $data = 'foo bar';

        $this->mockStreamWrapper->expects(self::once())->method('write')->with($data)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->stream_write($data));
    }

    /**
     * @test
     */
    public function unlinkTest()
    {
        $path = 'mockScheme1://foo/bar';

        $this->streamWrapperAdapter->expects(self::once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects(self::once())->method('unlink')->with($path)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->unlink($path));
    }

    /**
     * @test
     */
    public function url_statTest()
    {
        $path = 'mockScheme1://foo/bar';
        $flags = STREAM_URL_STAT_LINK;

        $this->streamWrapperAdapter->expects(self::once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects(self::once())->method('pathStat')->with($path, $flags)->will(self::returnValue(true));
        self::assertTrue($this->streamWrapperAdapter->url_stat($path, $flags));
    }
}
