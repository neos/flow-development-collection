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
use Neos\Flow\ResourceManagement\Streams\StreamWrapperAdapter;
use Neos\Flow\ResourceManagement\Streams\StreamWrapperInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the StreamWrapperAdapter class
 */
final class StreamWrapperAdapterTest extends UnitTestCase
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

    #[Test]
    public function getRegisteredStreamWrappersReturnsRegisteredStreamWrappers()
    {
        $mockStreamWrapper1ClassName = get_class($this->mockStreamWrapper);
        $mockStreamWrapper2 = $this->createStub(StreamWrapperInterface::class);
        $mockStreamWrapper2ClassName = get_class($mockStreamWrapper2);

        StreamWrapperAdapter::registerStreamWrapper('mockScheme1', $mockStreamWrapper1ClassName);
        StreamWrapperAdapter::registerStreamWrapper('mockScheme2', $mockStreamWrapper2ClassName);

        $registeredStreamWrappers = StreamWrapperAdapter::getRegisteredStreamWrappers();
        self::assertSame($mockStreamWrapper1ClassName, $registeredStreamWrappers['mockScheme1']);
        self::assertSame($mockStreamWrapper2ClassName, $registeredStreamWrappers['mockScheme2']);
    }

    #[Test]
    public function dir_closedirTest()
    {
        $this->mockStreamWrapper->expects($this->once())->method('closeDirectory')->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->dir_closedir());
    }

    #[Test]
    public function dir_opendirTest()
    {
        $path = 'mockScheme1://foo/bar';
        $options = 123;

        $this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects($this->once())->method('openDirectory')->with($path, $options)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->dir_opendir($path, $options));
    }

    #[Test]
    public function dir_readdirTest()
    {
        $this->mockStreamWrapper->expects($this->once())->method('readDirectory')->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->dir_readdir());
    }

    #[Test]
    public function dir_rewinddirTest()
    {
        $this->mockStreamWrapper->expects($this->once())->method('rewindDirectory')->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->dir_rewinddir());
    }

    #[Test]
    public function mkdirTest()
    {
        $path = 'mockScheme1://foo/bar';
        $mode = '0654';
        $options = STREAM_MKDIR_RECURSIVE;

        $this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects($this->once())->method('makeDirectory')->with($path, $mode, $options)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->mkdir($path, $mode, $options));
    }

    #[Test]
    public function renameTest()
    {
        $fromPath = 'mockScheme1://foo/bar';
        $toPath = 'mockScheme1://foo/baz';

        $this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($fromPath);
        $this->mockStreamWrapper->expects($this->once())->method('rename')->with($fromPath, $toPath)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->rename($fromPath, $toPath));
    }

    #[Test]
    public function rmdirTest()
    {
        $path = 'mockScheme1://foo/bar';
        $options = STREAM_MKDIR_RECURSIVE;

        $this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects($this->once())->method('removeDirectory')->with($path, $options)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->rmdir($path, $options));
    }

    #[Test]
    public function stream_castTest()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('stream_cast is not supported in HHVM (see http://docs.hhvm.com/manual/en/streamwrapper.stream-cast.php)');
        }
        $castAs = STREAM_CAST_FOR_SELECT;

        $this->mockStreamWrapper->expects($this->once())->method('cast')->with($castAs)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_cast($castAs));
    }

    #[Test]
    public function stream_closeTest()
    {
        $this->mockStreamWrapper->expects($this->once())->method('close');
        $this->streamWrapperAdapter->stream_close();
    }

    #[Test]
    public function stream_eofTest()
    {
        $this->mockStreamWrapper->expects($this->once())->method('isAtEof')->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_eof());
    }

    #[Test]
    public function stream_flushTest()
    {
        $this->mockStreamWrapper->expects($this->once())->method('flush')->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_flush());
    }

    #[Test]
    public function stream_lockTest()
    {
        $operation = LOCK_SH;

        $this->mockStreamWrapper->expects($this->once())->method('lock')->with($operation)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_lock($operation));
    }

    #[Test]
    public function stream_unlockTest()
    {
        $operation = LOCK_UN;

        $this->mockStreamWrapper->expects($this->once())->method('unlock')->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_lock($operation));
    }

    #[Test]
    public function stream_openTest()
    {
        $path = 'mockScheme1://foo/bar';
        $mode = 'r+';
        $options = STREAM_REPORT_ERRORS;
        $openedPath = '';

        $this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects($this->once())->method('open')->with($path, $mode, $options, $openedPath)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_open($path, $mode, $options, $openedPath));
    }

    #[Test]
    public function stream_readTest()
    {
        $count = 123;

        $this->mockStreamWrapper->expects($this->once())->method('read')->with($count)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_read($count));
    }

    #[Test]
    public function stream_seekTest()
    {
        $offset = 123;

        $this->mockStreamWrapper->expects($this->once())->method('seek')->with($offset, SEEK_SET)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_seek($offset));
    }

    #[Test]
    public function stream_seekTest2()
    {
        $offset = 123;
        $whence = SEEK_END;

        $this->mockStreamWrapper->expects($this->once())->method('seek')->with($offset, $whence)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_seek($offset, $whence));
    }

    #[Test]
    public function stream_set_optionTest()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('stream_set_option is not supported in HHVM (see http://docs.hhvm.com/manual/en/streamwrapper.stream-set-option.php)');
        }
        $option = STREAM_OPTION_READ_TIMEOUT;
        $arg1 = 123;
        $arg2 = 123000000;

        $this->mockStreamWrapper->expects($this->once())->method('setOption')->with($option, $arg1, $arg2)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_set_option($option, $arg1, $arg2));
    }

    #[Test]
    public function stream_statTest()
    {
        $this->mockStreamWrapper->expects($this->once())->method('resourceStat')->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_stat());
    }

    #[Test]
    public function stream_tellTest()
    {
        $this->mockStreamWrapper->expects($this->once())->method('tell')->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_tell());
    }

    #[Test]
    public function stream_writeTest()
    {
        $data = 'foo bar';

        $this->mockStreamWrapper->expects($this->once())->method('write')->with($data)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->stream_write($data));
    }

    #[Test]
    public function unlinkTest()
    {
        $path = 'mockScheme1://foo/bar';

        $this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects($this->once())->method('unlink')->with($path)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->unlink($path));
    }

    #[Test]
    public function url_statTest()
    {
        $path = 'mockScheme1://foo/bar';
        $flags = STREAM_URL_STAT_LINK;

        $this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
        $this->mockStreamWrapper->expects($this->once())->method('pathStat')->with($path, $flags)->willReturn((true));
        self::assertTrue($this->streamWrapperAdapter->url_stat($path, $flags));
    }
}
