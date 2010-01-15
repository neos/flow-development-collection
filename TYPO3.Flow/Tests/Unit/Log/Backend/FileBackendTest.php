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
		$logFileUrl = \vfsStream::url('testDirectory') . '/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl));
		$backend->open();
		$this->assertTrue(\vfsStreamWrapper::getRoot()->hasChild('test.log'));
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Log\Exception\CouldNotOpenResourceException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function openDoesNotCreateParentDirectoriesByDefault() {
		$logFileUrl = \vfsStream::url('testDirectory') . '/foo/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl));
		$backend->open();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function openCreatesParentDirectoriesIfTheOptionSaysSo() {
		$logFileUrl = \vfsStream::url('testDirectory') . '/foo/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl, 'createParentDirectories' => TRUE));
		$backend->open();
		$this->assertTrue(\vfsStreamWrapper::getRoot()->hasChild('foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function appendRendersALogEntryAndAppendsItToTheLogfile() {
		$logFileUrl = \vfsStream::url('testDirectory') . '/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl));
		$backend->open();

		$backend->append('foo');

		$this->assertSame(52 + strlen(PHP_EOL), \vfsStreamWrapper::getRoot()->getChild('test.log')->size());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function appendIgnoresMessagesAboveTheSeverityThreshold() {
		$logFileUrl = \vfsStream::url('testDirectory') . '/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileUrl' => $logFileUrl));
		$backend->setSeverityThreshold(LOG_EMERG);
		$backend->open();

		$backend->append('foo', LOG_INFO);

		$this->assertSame(0, \vfsStreamWrapper::getRoot()->getChild('test.log')->size());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function logFileIsRotatedIfMaximumSizeIsExceeded() {
		$this->markTestSkipped('vfsStream does not support touch() and rename()...');

		$logFileUrl = \vfsStream::url('testDirectory') . '/test.log';
		file_put_contents($logFileUrl, 'twentybytesofcontent');

		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Log\Backend\FileBackend'), array('dummy'), array(array('logFileUrl' => $logFileUrl)));
		$backend->_set('maximumLogFileSize', 10);
		$backend->setLogFilesToKeep(1);
		$backend->open();

		$this->assertFalse(\vfsStreamWrapper::getRoot()->hasChild('test.log'));
		$this->assertTrue(\vfsStreamWrapper::getRoot()->hasChild('test.log.1'));
	}

}
?>