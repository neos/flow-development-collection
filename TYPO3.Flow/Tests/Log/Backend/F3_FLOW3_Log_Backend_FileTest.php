<?php
declare(ENCODING = 'utf-8');

namespace F3\FLOW3\Log\Backend;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class FileTest extends \F3\Testing\BaseTestCase {

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
		$backend = new \F3\FLOW3\Log\Backend\File(array('logFileURL' => $logFileURL));
		$backend->open();
		$this->assertTrue(\vfsStreamWrapper::getRoot()->hasChild('test.log'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function appendRendersALogEntryAndAppendsItToTheLogfile() {
		$logFileURL = \vfsStream::url('testDirectory') . '/test.log';
		$backend = new \F3\FLOW3\Log\Backend\File(array('logFileURL' => $logFileURL));
		$backend->open();

		$backend->append('foo');

		$this->assertSame(52, \vfsStreamWrapper::getRoot()->getChild('test.log')->size());
	}
}
?>