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

/**
 * @package FLOW3
 * @subpackage Log
 * @version $Id$
 */

require_once('vfs/vfsStream.php');

/**
 * Testcase for the File Backend
 *
 * @package FLOW3
 * @subpackage Log
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function appendRendersALogEntryAndAppendsItToTheLogfile() {
		$logFileURL = \vfsStream::url('testDirectory') . '/test.log';
		$backend = new \F3\FLOW3\Log\Backend\FileBackend(array('logFileURL' => $logFileURL));
		$backend->open();

		$backend->append('foo');

		$this->assertSame(53, \vfsStreamWrapper::getRoot()->getChild('test.log')->size());
	}
}
?>