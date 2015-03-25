<?php
namespace TYPO3\Flow\Tests\Unit\Resource\Streams;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the StreamWrapperAdapter class
 *
 */
class StreamWrapperAdapterTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Resource\Streams\StreamWrapperAdapter
	 */
	protected $streamWrapperAdapter;

	/**
	 * @var \TYPO3\Flow\Resource\Streams\StreamWrapperInterface
	 */
	protected $mockStreamWrapper;


	public function setUp() {
		$this->streamWrapperAdapter = $this->getAccessibleMock('TYPO3\Flow\Resource\Streams\StreamWrapperAdapter', array('createStreamWrapper'));
		$this->mockStreamWrapper = $this->getMock('TYPO3\Flow\Resource\Streams\StreamWrapperInterface');
		$this->streamWrapperAdapter->_set('streamWrapper', $this->mockStreamWrapper);
	}

	/**
	 * @test
	 */
	public function getRegisteredStreamWrappersReturnsRegisteredStreamWrappers() {
		$mockStreamWrapper1ClassName = get_class($this->mockStreamWrapper);
		$mockStreamWrapper2 = $this->getMock('TYPO3\Flow\Resource\Streams\StreamWrapperInterface');
		$mockStreamWrapper2ClassName = get_class($mockStreamWrapper2);

		\TYPO3\Flow\Resource\Streams\StreamWrapperAdapter::registerStreamWrapper('mockScheme1', $mockStreamWrapper1ClassName);
		\TYPO3\Flow\Resource\Streams\StreamWrapperAdapter::registerStreamWrapper('mockScheme2', $mockStreamWrapper2ClassName);

		$registeredStreamWrappers = \TYPO3\Flow\Resource\Streams\StreamWrapperAdapter::getRegisteredStreamWrappers();
		$this->assertSame($mockStreamWrapper1ClassName, $registeredStreamWrappers['mockScheme1']);
		$this->assertSame($mockStreamWrapper2ClassName, $registeredStreamWrappers['mockScheme2']);
	}

	/**
	 * @test
	 */
	public function dir_closedirTest() {
		$this->mockStreamWrapper->expects($this->once())->method('closeDirectory')->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->dir_closedir());
	}

	/**
	 * @test
	 */
	public function dir_opendirTest() {
		$path = 'mockScheme1://foo/bar';
		$options = 123;

		$this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
		$this->mockStreamWrapper->expects($this->once())->method('openDirectory')->with($path, $options)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->dir_opendir($path, $options));
	}

	/**
	 * @test
	 */
	public function dir_readdirTest() {
		$this->mockStreamWrapper->expects($this->once())->method('readDirectory')->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->dir_readdir());
	}

	/**
	 * @test
	 */
	public function dir_rewinddirTest() {
		$this->mockStreamWrapper->expects($this->once())->method('rewindDirectory')->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->dir_rewinddir());
	}

	/**
	 * @test
	 */
	public function mkdirTest() {
		$path = 'mockScheme1://foo/bar';
		$mode = '0654';
		$options = STREAM_MKDIR_RECURSIVE;

		$this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
		$this->mockStreamWrapper->expects($this->once())->method('makeDirectory')->with($path, $mode, $options)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->mkdir($path, $mode, $options));
	}

	/**
	 * @test
	 */
	public function renameTest() {
		$fromPath = 'mockScheme1://foo/bar';
		$toPath = 'mockScheme1://foo/baz';

		$this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($fromPath);
		$this->mockStreamWrapper->expects($this->once())->method('rename')->with($fromPath, $toPath)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->rename($fromPath, $toPath));
	}

	/**
	 * @test
	 */
	public function rmdirTest() {
		$path = 'mockScheme1://foo/bar';
		$options = STREAM_MKDIR_RECURSIVE;

		$this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
		$this->mockStreamWrapper->expects($this->once())->method('removeDirectory')->with($path, $options)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->rmdir($path, $options));
	}

	/**
	 * @test
	 */
	public function stream_castTest() {
		$castAs = STREAM_CAST_FOR_SELECT;

		$this->mockStreamWrapper->expects($this->once())->method('cast')->with($castAs)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_cast($castAs));
	}

	/**
	 * @test
	 */
	public function stream_closeTest() {
		$this->mockStreamWrapper->expects($this->once())->method('close');
		$this->streamWrapperAdapter->stream_close();
	}

	/**
	 * @test
	 */
	public function stream_eofTest() {
		$this->mockStreamWrapper->expects($this->once())->method('isAtEof')->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_eof());
	}

	/**
	 * @test
	 */
	public function stream_flushTest() {
		$this->mockStreamWrapper->expects($this->once())->method('flush')->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_flush());
	}

	/**
	 * @test
	 */
	public function stream_lockTest() {
		$operation = LOCK_SH;

		$this->mockStreamWrapper->expects($this->once())->method('lock')->with($operation)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_lock($operation));
	}

	/**
	 * @test
	 */
	public function stream_unlockTest() {
		$operation = LOCK_UN;

		$this->mockStreamWrapper->expects($this->once())->method('unlock')->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_lock($operation));
	}

	/**
	 * @test
	 */
	public function stream_openTest() {
		$path = 'mockScheme1://foo/bar';
		$mode = 'r+';
		$options = STREAM_REPORT_ERRORS;
		$openedPath = '';

		$this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
		$this->mockStreamWrapper->expects($this->once())->method('open')->with($path, $mode, $options, $openedPath)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_open($path, $mode, $options, $openedPath));
	}

	/**
	 * @test
	 */
	public function stream_readTest() {
		$count = 123;

		$this->mockStreamWrapper->expects($this->once())->method('read')->with($count)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_read($count));
	}

	/**
	 * @test
	 */
	public function stream_seekTest() {
		$offset = 123;

		$this->mockStreamWrapper->expects($this->once())->method('seek')->with($offset, SEEK_SET)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_seek($offset));
	}

	/**
	 * @test
	 */
	public function stream_seekTest2() {
		$offset = 123;
		$whence = SEEK_END;

		$this->mockStreamWrapper->expects($this->once())->method('seek')->with($offset, $whence)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_seek($offset, $whence));
	}

	/**
	 * @test
	 */
	public function stream_set_optionTest() {
		$option = STREAM_OPTION_READ_TIMEOUT;
		$arg1 = 123;
		$arg2 = 123000000;

		$this->mockStreamWrapper->expects($this->once())->method('setOption')->with($option, $arg1, $arg2)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_set_option($option, $arg1, $arg2));
	}

	/**
	 * @test
	 */
	public function stream_statTest() {
		$this->mockStreamWrapper->expects($this->once())->method('resourceStat')->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_stat());
	}

	/**
	 * @test
	 */
	public function stream_tellTest() {
		$this->mockStreamWrapper->expects($this->once())->method('tell')->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_tell());
	}

	/**
	 * @test
	 */
	public function stream_writeTest() {
		$data = 'foo bar';

		$this->mockStreamWrapper->expects($this->once())->method('write')->with($data)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->stream_write($data));
	}

	/**
	 * @test
	 */
	public function unlinkTest() {
		$path = 'mockScheme1://foo/bar';

		$this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
		$this->mockStreamWrapper->expects($this->once())->method('unlink')->with($path)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->unlink($path));
	}

	/**
	 * @test
	 */
	public function url_statTest() {
		$path = 'mockScheme1://foo/bar';
		$flags = STREAM_URL_STAT_LINK;

		$this->streamWrapperAdapter->expects($this->once())->method('createStreamWrapper')->with($path);
		$this->mockStreamWrapper->expects($this->once())->method('pathStat')->with($path, $flags)->will($this->returnValue(TRUE));
		$this->assertTrue($this->streamWrapperAdapter->url_stat($path, $flags));
	}
}
