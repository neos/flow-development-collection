<?php
namespace TYPO3\FLOW3\Tests\Unit\Error;

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
 * Testcase for the Message object
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class MessageTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsMessage() {
		$someMessage = 'The message';
		$someMessageCode = 12345;
		$message = new \TYPO3\FLOW3\Error\Message($someMessage, $someMessageCode);
		$this->assertEquals($someMessage, $message->getMessage());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsArguments() {
		$someArguments = array('Foo', 'Bar');
		$someMessageCode = 12345;
		$message = new \TYPO3\FLOW3\Error\Message('', $someMessageCode, $someArguments);
		$this->assertEquals($someArguments, $message->getArguments());
	}

	/**
	 * @test
	 * @author Christian Müller <christian.mueller@typo3.org>
	 */
	public function constructorSetsCode() {
		$someMessage = 'The message';
		$someMessageCode = 12345;
		$message = new \TYPO3\FLOW3\Error\Message($someMessage, $someMessageCode);
		$this->assertEquals($someMessageCode, $message->getCode());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsTheMessageTextIfNoArgumentsAreSpecified() {
		$someMessage = 'The message';
		$someMessageCode = 12345;
		$message = new \TYPO3\FLOW3\Error\Message($someMessage, $someMessageCode);
		$this->assertEquals($someMessage, $message->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReplacesArgumentsInTheMessageText() {
		$someMessage = 'The message with %2$s and %1$s';
		$someArguments = array('Foo', 'Bar');
		$someMessageCode = 12345;
		$message = new \TYPO3\FLOW3\Error\Message($someMessage, $someMessageCode, $someArguments);

		$expectedResult = 'The message with Bar and Foo';
		$actualResult = $message->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertingTheMessageToStringRendersIt() {
		$someMessage = 'The message with %2$s and %1$s';
		$someArguments = array('Foo', 'Bar');
		$someMessageCode = 12345;
		$message = new \TYPO3\FLOW3\Error\Message($someMessage, $someMessageCode, $someArguments);

		$expectedResult = 'The message with Bar and Foo (#' . $someMessageCode . ')';
		$actualResult = (string)$message;
		$this->assertEquals($expectedResult, $actualResult);
	}
}

?>