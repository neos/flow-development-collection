<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Log;

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

/**
 * Testcase for the Simple File Logger
 *
 * @package FLOW3
 * @subpackage Log
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class SimpleFileLoggerTest extends F3::Testing::BaseTestCase {

	/**
	 * @var Directory used for testing.
	 */
	protected $testDirectory;

	/**
	 * Sets up this testcase
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$environment = $this->objectManager->getObject('F3::FLOW3::Utility::Environment');
		$this->testDirectory = $environment->getPathToTemporaryDirectory();
	}

	/**
	 * Cleans up after testing ...
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tearDown() {
		if (file_exists($this->testDirectory . 'simplefileloggertest.log')) unlink ($this->testDirectory . 'simplefileloggertest.log');
	}

	/**
	 * Checks if log messages are written to the right file
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logMessagesAreWrittenIntoAFile() {
		$fileLogger = new F3::FLOW3::Log::SimpleFileLogger($this->testDirectory . 'simplefileloggertest.log');
		$message = 'Test Message' . microtime();
		$fileLogger->log($message, 0, array('testkey' => 'testvalue'));

		$this->assertFileExists($this->testDirectory . 'simplefileloggertest.log', 'Log file simplefileloggertest.log not found!');

		$content = file_get_contents ($this->testDirectory . 'simplefileloggertest.log');
		$stringFound = strstr ($content, $message);
		$this->assertTrue((strlen($stringFound) > 0), 'Didn\'t find the test string in the log file!');
	}

}
?>