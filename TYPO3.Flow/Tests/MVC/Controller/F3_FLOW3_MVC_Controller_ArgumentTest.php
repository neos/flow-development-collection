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
 * Testcase for the MVC Controller Argument
 *
 * @package		FLOW3
 * @version 	$Id:F3_FLOW3_MVC_Controller_ArgumentsTest.php 201 2007-09-10 23:58:30Z Andi $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Controller_ArgumentTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function argumentScopeIsPrototype() {
		$argument1 = $this->componentManager->getComponent('F3_FLOW3_MVC_Controller_Argument', 'test');
		$argument2 = $this->componentManager->getComponent('F3_FLOW3_MVC_Controller_Argument', 'test');
		$this->assertNotSame($argument1, $argument2, 'Arguments seem to be identical.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructingArgumentWithoutNameThrowsException() {
		try {
			$this->componentManager->getComponent('F3_FLOW3_MVC_Controller_Argument');
			$this->fail('Constructing an argument without specifying a name did not throw an exception.');
		} catch (InvalidArgumentException $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructingArgumentWithInvalidNameThrowsException() {
		try {
			$this->componentManager->getComponent('F3_FLOW3_MVC_Controller_Argument', new ArrayObject());
			$this->fail('Constructing an argument with invalid name did not throw an exception.');
		} catch (InvalidArgumentException $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingDataTypeToConstructorReallySetsTheDataType() {
		$argument = $this->componentManager->getComponent('F3_FLOW3_MVC_Controller_Argument', 'dummy', 'number');
		$this->assertEquals('number', $argument->getDataType(), 'The specified data type has not been set correctly.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortNameProvidesFluentInterface() {
		$argument = $this->componentManager->getComponent('F3_FLOW3_MVC_Controller_Argument', 'dummy');
		$returnedArgument = $argument->setShortName('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValueProvidesFluentInterface() {
		$argument = $this->componentManager->getComponent('F3_FLOW3_MVC_Controller_Argument', 'dummy');
		$returnedArgument = $argument->setValue('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortHelpMessageProvidesFluentInterface() {
		$argument = $this->componentManager->getComponent('F3_FLOW3_MVC_Controller_Argument', 'dummy');
		$returnedArgument = $argument->setShortHelpMessage('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toStringReturnsTheStringVersionOfTheArgumentsValue() {
		$argument = $this->componentManager->getComponent('F3_FLOW3_MVC_Controller_Argument', 'dummy');
		$argument->setValue(123);

		$this->assertSame((string)$argument, '123', 'The returned argument is not a string.');
		$this->assertNotSame((string)$argument, 123, 'The returned argument is identical to the set value.');
	}
}
?>