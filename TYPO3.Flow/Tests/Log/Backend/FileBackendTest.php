<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Log\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('vfs/vfsStream.php');

/**
 * Testcase for the File Backend
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FileBackendTest extends \F3\Testing\BaseTestCase {

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theLogFileIsOpenedWithOpen() {
		$logFileURL = \vfsStream::url('testDirectory') . '/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileURL' => $logFileURL));
		$backend->open();
		$this->assertTrue(\vfsStreamWrapper::getRoot()->hasChild('test.log'));
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Log\Exception\CouldNotOpenResource
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function openDoesNotCreateParentDirectoriesByDefault() {
		$logFileURL = \vfsStream::url('testDirectory') . '/foo/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileURL' => $logFileURL));
		$backend->open();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function openCreatesParentDirectoriesIfTheOptionSaysSo() {
		$logFileURL = \vfsStream::url('testDirectory') . '/foo/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileURL' => $logFileURL, 'createParentDirectories' => TRUE));
		$backend->open();
		$this->assertTrue(\vfsStreamWrapper::getRoot()->hasChild('foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function appendRendersALogEntryAndAppendsItToTheLogfile() {
		$logFileURL = \vfsStream::url('testDirectory') . '/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileURL' => $logFileURL));
		$backend->open();

		$backend->append('foo');

		$this->assertSame(52 + strlen(PHP_EOL), \vfsStreamWrapper::getRoot()->getChild('test.log')->size());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function appendIgnoresMessagesAboveTheSeverityThreshold() {
		$logFileURL = \vfsStream::url('testDirectory') . '/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileURL' => $logFileURL));
		$backend->setSeverityThreshold(\F3\FLOW3\Log\LoggerInterface::SEVERITY_EMERGENCY);
		$backend->open();

		$backend->append('foo', \F3\FLOW3\Log\LoggerInterface::SEVERITY_INFO);

		$this->assertSame(0, \vfsStreamWrapper::getRoot()->getChild('test.log')->size());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function logFileIsRotatedIfMaximumSizeIsExceeded() {
		$this->markTestSkipped('vfsStream does not support touch() and rename()...');

		$logFileURL = \vfsStream::url('testDirectory') . '/test.log';
		file_put_contents($logFileURL, 'twentybytesofcontent');

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Log\Backend\FileBackend'), array('dummy'), array(array('logFileURL' => $logFileURL)));
		$backend->_set('maximumLogFileSize', 10);
		$backend->setLogFilesToKeep(1);
		$backend->open();

		$this->assertFalse(\vfsStreamWrapper::getRoot()->hasChild('test.log'));
		$this->assertTrue(\vfsStreamWrapper::getRoot()->hasChild('test.log.1'));
	}

}
?>