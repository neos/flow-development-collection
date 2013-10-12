<?php
namespace TYPO3\Flow\Tests\Unit\Error;

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
 * Testcase for the Message object
 *
 */
class MessageTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function constructorSetsMessage() {
		$someMessage = 'The message';
		$someMessageCode = 12345;
		$message = new \TYPO3\Flow\Error\Message($someMessage, $someMessageCode);
		$this->assertEquals($someMessage, $message->getMessage());
	}

	/**
	 * @test
	 */
	public function constructorSetsArguments() {
		$someArguments = array('Foo', 'Bar');
		$someMessageCode = 12345;
		$message = new \TYPO3\Flow\Error\Message('', $someMessageCode, $someArguments);
		$this->assertEquals($someArguments, $message->getArguments());
	}

	/**
	 * @test
	 */
	public function constructorSetsCode() {
		$someMessage = 'The message';
		$someMessageCode = 12345;
		$message = new \TYPO3\Flow\Error\Message($someMessage, $someMessageCode);
		$this->assertEquals($someMessageCode, $message->getCode());
	}

	/**
	 * @test
	 */
	public function renderReturnsTheMessageTextIfNoArgumentsAreSpecified() {
		$someMessage = 'The message';
		$someMessageCode = 12345;
		$message = new \TYPO3\Flow\Error\Message($someMessage, $someMessageCode);
		$this->assertEquals($someMessage, $message->render());
	}

	/**
	 * @test
	 */
	public function renderReplacesArgumentsInTheMessageText() {
		$someMessage = 'The message with %2$s and %1$s';
		$someArguments = array('Foo', 'Bar');
		$someMessageCode = 12345;
		$message = new \TYPO3\Flow\Error\Message($someMessage, $someMessageCode, $someArguments);

		$expectedResult = 'The message with Bar and Foo';
		$actualResult = $message->render();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function convertingTheMessageToStringRendersIt() {
		$someMessage = 'The message with %2$s and %1$s';
		$someArguments = array('Foo', 'Bar');
		$someMessageCode = 12345;
		$message = new \TYPO3\Flow\Error\Message($someMessage, $someMessageCode, $someArguments);

		$expectedResult = 'The message with Bar and Foo';
		$actualResult = (string)$message;
		$this->assertEquals($expectedResult, $actualResult);
	}
}
