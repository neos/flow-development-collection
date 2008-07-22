<?php
declare(ENCODING = 'utf-8');

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
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_PointcutTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the Utility Environment class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_PointcutTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Utility_EnvironmentTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPathToTemporaryDirectoryReturnsPathWithTrailingSlash() {
		$configuration = $this->componentFactory->getComponent('F3_FLOW3_Configuration_Manager')->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_FLOW3);
		$environment = new F3_FLOW3_Utility_Environment($configuration->utility->environment);
		$path = $environment->getPathToTemporaryDirectory();
		$this->assertEquals(DIRECTORY_SEPARATOR, substr($path, -1, 1), 'The temporary path did not end with slash (as in DIRECTORY_SEPARATOR).');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPathToTemporaryDirectoryReturnsAnExistingPath() {
		$configuration = $this->componentFactory->getComponent('F3_FLOW3_Configuration_Manager')->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_FLOW3);
		$environment = new F3_FLOW3_Utility_Environment($configuration->utility->environment);
		$path = $environment->getPathToTemporaryDirectory();
		$this->assertTrue(file_exists($path), 'The temporary path does not exist.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getScriptPathAndFilenameReturnsCorrectPathAndFilename() {
		$expectedPathAndFilename = '/this/is/the/file.php';
		$configuration = $this->componentFactory->getComponent('F3_FLOW3_Configuration_Manager')->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_FLOW3);
		$environment = new F3_FLOW3_Utility_MockEnvironment($configuration->utility->environment);
		$environment->SERVER = array(
			'SCRIPT_FILENAME' => '/this/is/the/file.php'
		);
		$returnedPathAndFilename = $environment->getScriptPathAndFilename();
		$this->assertEquals($expectedPathAndFilename, $returnedPathAndFilename, 'The returned path did not match the expected value.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getScriptPathAndFilenameReturnsCorrectPathAndFilenameForWindowsStylePath() {
		$expectedPathAndFilename = '/this/is/the/file.php';
		$configuration = $this->componentFactory->getComponent('F3_FLOW3_Configuration_Manager')->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_FLOW3);
		$environment = new F3_FLOW3_Utility_MockEnvironment($configuration->utility->environment);
		$environment->SERVER = array(
			'SCRIPT_FILENAME' => '\\this\\is\\the\\file.php'
		);
		$returnedPathAndFilename = $environment->getScriptPathAndFilename();
		$this->assertEquals($expectedPathAndFilename, $returnedPathAndFilename, 'The returned path did not match the expected value.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRequestURIReturnsExpectedURI() {
		$expectedURIString = 'http://flow3.typo3.org/is/the/base/for/typo3?5=0';
		$configuration = $this->componentFactory->getComponent('F3_FLOW3_Configuration_Manager')->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_FLOW3);
		$environment = new F3_FLOW3_Utility_MockEnvironment($configuration->utility->environment);
		$environment->SERVER = array(
			'HTTP_HOST' => 'flow3.typo3.org',
			'QUERY_STRING' => '5=0',
			'SCRIPT_FILENAME' => '/is/the/base/for/typo3'
		);
		$returnedURIString = (string)$environment->getRequestURI();
		$this->assertEquals($expectedURIString, $returnedURIString, 'The URI returned did not match the expected value.');
	}
}
?>