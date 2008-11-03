<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Controller;

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
 * Testcase for the MVC Controller Arguments
 *
 * @package		FLOW3
 * @version 	$Id:F3::FLOW3::MVC::Controller::ArgumentsTest.php 201 2007-09-10 23:58:30Z Andi $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ArgumentsTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function argumentsObjectIsOfScopePrototype() {
		$arguments1 = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');
		$arguments2 = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');
		$this->assertNotSame($arguments1, $arguments2, 'The arguments object is not of scope prototype!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingAnArgumentManuallyWorks() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');
		$newArgument = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Argument', 'argumentName1234');

		$arguments->addArgument($newArgument);
		$this->assertSame($newArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingAnArgumentReplacesArgumentWithSameName() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');

		$firstArgument = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Argument', 'argumentName1234');
		$arguments->addArgument($firstArgument);

		$secondArgument = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Argument', 'argumentName1234');
		$arguments->addArgument($secondArgument);

		$this->assertSame($secondArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addNewArgumentProvidesFluentInterface() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');
		$newArgument = $arguments->addNewArgument('someArgument');
		$this->assertType('F3::FLOW3::MVC::Controller::Argument', $newArgument, 'addNewArgument() did not return an argument object.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingArgumentThroughArrayAccessWorks() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');
		$argument = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Argument', 'argumentName1234');
		$arguments[] = $argument;
		$this->assertTrue($arguments->hasArgument('argumentName1234'), 'Added argument does not exist.');
		$this->assertSame($argument, $arguments->getArgument('argumentName1234'), 'Added and retrieved arguments are not the same.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function retrievingArgumentThroughArrayAccessWorks() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');
		$newArgument = $arguments->addNewArgument('someArgument');
		$this->assertSame($newArgument, $arguments['someArgument'], 'Argument retrieved by array access is not the one we added.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentWithNonExistingArgumentNameThrowsException() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');
		try {
			$arguments->getArgument('someArgument');
			$this->fail('getArgument() did not throw an exception although the specified argument does not exist.');
		} catch (F3::FLOW3::MVC::Exception::NoSuchArgument $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function issetReturnsCorrectResult() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');
		$this->assertFalse(isset($arguments['someArgument']), 'isset() did not return FALSE.');
		$arguments->addNewArgument('someArgument');
		$this->assertTrue(isset($arguments['someArgument']), 'isset() did not return TRUE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentNamesReturnsNamesOfAddedArguments() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');
		$arguments->addNewArgument('first');
		$arguments->addNewArgument('second');
		$arguments->addNewArgument('third');

		$expectedArgumentNames = array('first', 'second', 'third');
		$this->assertEquals($expectedArgumentNames, $arguments->getArgumentNames(), 'Returned argument names were not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentShortNamesReturnsShortNamesOfAddedArguments() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');
		$argument = $arguments->addNewArgument('first')->setShortName('a');
		$arguments->addNewArgument('second')->setShortName('b');
		$arguments->addNewArgument('third')->setShortName('c');

		$expectedShortNames = array('a', 'b', 'c');
		$this->assertEquals($expectedShortNames, $arguments->getArgumentShortNames(), 'Returned argument short names were not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addNewArgumentCreatesAndAddsNewArgument() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');

		$addedArgument = $arguments->addNewArgument('dummyName');
		$this->assertType('F3::FLOW3::MVC::Controller::Argument', $addedArgument, 'addNewArgument() either did not add a new argument or did not return it.');

		$retrievedArgument = $arguments['dummyName'];
		$this->assertSame($addedArgument, $retrievedArgument, 'The added and the retrieved argument are not the same.');

		$this->assertEquals('dummyName', $addedArgument->getName(), 'The name of the added argument is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addNewArgumentAssumesTextDataTypeByDefault() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');

		$addedArgument = $arguments->addNewArgument('dummyName');
		$this->assertEquals('Text', $addedArgument->getDataType(), 'addNewArgument() did not create an argument of type "Text" by default.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addNewArgumentCanAddArgumentsMarkedAsRequired() {
		$arguments = $this->componentManager->getComponent('F3::FLOW3::MVC::Controller::Arguments');

		$addedArgument = $arguments->addNewArgument('dummyName', 'Text', TRUE);
		$this->assertTrue($addedArgument->isRequired(), 'addNewArgument() did not create an argument that is marked as required.');
	}
}
?>