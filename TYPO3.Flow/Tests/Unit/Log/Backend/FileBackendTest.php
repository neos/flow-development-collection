<?php
namespace TYPO3\FLOW3\Tests\Unit\Log\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Testcase for the File Backend
 *
 */
class FileBackendTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 */
	public function setUp() {
		vfsStream::setup('testDirectory');
	}

	/**
	 * @test
	 */
	public function theLogFileIsOpenedWithOpen() {
		$logFileUrl = vfsStream::url('testDirectory') . '/test.log';
		$backend = new \TYPO3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl));
		$backend->open();
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('test.log'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Log\Exception\CouldNotOpenResourceException
	 */
	public function openDoesNotCreateParentDirectoriesByDefault() {
		$logFileUrl = vfsStream::url('testDirectory') . '/foo/test.log';
		$backend = new \TYPO3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl));
		$backend->open();
	}

	/**
	 * @test
	 */
	public function openCreatesParentDirectoriesIfTheOptionSaysSo() {
		$logFileUrl = vfsStream::url('testDirectory') . '/foo/test.log';
		$backend = new \TYPO3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl, 'createParentDirectories' => TRUE));
		$backend->open();
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('foo'));
	}

	/**
	 * @test
	 */
	public function appendRendersALogEntryAndAppendsItToTheLogfile() {
		$logFileUrl = vfsStream::url('testDirectory') . '/test.log';
		$backend = new \TYPO3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl));
		$backend->open();

		$backend->append('foo');

		$pidOffset = function_exists('posix_getpid') ? 10 : 0;
		$this->assertSame(53 + $pidOffset + strlen(PHP_EOL), vfsStreamWrapper::getRoot()->getChild('test.log')->size());
	}

	/**
	 * @test
	 */
	public function appendRendersALogEntryWithRemoteIpAddressAndAppendsItToTheLogfile() {
		$logFileUrl = vfsStream::url('testDirectory') . '/test.log';
		$backend = new \TYPO3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl));
		$backend->setLogIpAddress(TRUE);
		$backend->open();

		$backend->append('foo');

		$pidOffset = function_exists('posix_getpid') ? 10 : 0;
		$this->assertSame(68 + $pidOffset + strlen(PHP_EOL), vfsStreamWrapper::getRoot()->getChild('test.log')->size());
	}

	/**
	 * @test
	 */
	public function appendIgnoresMessagesAboveTheSeverityThreshold() {
		$logFileUrl = vfsStream::url('testDirectory') . '/test.log';
		$backend = new \TYPO3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl));
		$backend->setSeverityThreshold(LOG_EMERG);
		$backend->open();

		$backend->append('foo', LOG_INFO);

		$this->assertSame(0, vfsStreamWrapper::getRoot()->getChild('test.log')->size());
	}

	/**
	 * @test
	 */
	public function logFileIsRotatedIfMaximumSizeIsExceeded() {
		$this->markTestSkipped('vfsStream does not support touch() and rename(), see http://bugs.php.net/38025...');

		$logFileUrl = vfsStream::url('testDirectory') . '/test.log';
		file_put_contents($logFileUrl, 'twentybytesofcontent');

		$backend = $this->getAccessibleMock('TYPO3\FLOW3\Log\Backend\FileBackend', array('dummy'), array(array('logFileUrl' => $logFileUrl)));
		$backend->_set('maximumLogFileSize', 10);
		$backend->setLogFilesToKeep(1);
		$backend->open();

		$this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('test.log'));
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('test.log.1'));
	}

}
?>