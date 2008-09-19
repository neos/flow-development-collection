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
 * Testcase for the MVC Controller Argument
 *
 * @package		FLOW3
 * @version 	$Id:F3::FLOW3::MVC::Controller::ArgumentsTest.php 201 2007-09-10 23:58:30Z Andi $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ArgumentTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function argumentScopeIsPrototype() {
		$argument1 = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'test');
		$argument2 = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'test');
		$this->assertNotSame($argument1, $argument2, 'Arguments seem to be identical.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructingArgumentWithoutNameThrowsException() {
		try {
			$this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument');
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
			$this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', new ::ArrayObject());
			$this->fail('Constructing an argument with invalid name did not throw an exception.');
		} catch (InvalidArgumentException $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingDataTypeToConstructorReallySetsTheDataType() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy', 'Number');
		$this->assertEquals('Number', $argument->getDataType(), 'The specified data type has not been set correctly.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortNameProvidesFluentInterface() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy');
		$returnedArgument = $argument->setShortName('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValueProvidesFluentInterface() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy');
		$returnedArgument = $argument->setValue('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortHelpMessageProvidesFluentInterface() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy');
		$returnedArgument = $argument->setShortHelpMessage('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toStringReturnsTheStringVersionOfTheArgumentsValue() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy');
		$argument->setValue(123);

		$this->assertSame((string)$argument, '123', 'The returned argument is not a string.');
		$this->assertNotSame((string)$argument, 123, 'The returned argument is identical to the set value.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dataTypeValidatorCanBeAFullClassname() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'SomeArgument', 'F3::FLOW3::Validation::Validator::Text');

		$this->assertType('F3::FLOW3::Validation::Validator::Text', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dataTypeValidatorCanBeAShortName() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'SomeArgument', 'Text');

		$this->assertType('F3::FLOW3::Validation::Validator::Text', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function defaultDataTypeIsText() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'SomeArgument');

		$this->assertType('F3::FLOW3::Validation::Validator::Text', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorChainCreatesANewValidatorChainObject() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy');
		$argument->setNewValidatorChain(array('F3::FLOW3::Validation::Validator::Text', 'F3::FLOW3::Validation::Validator::EmailAddress'));

		$this->assertType('F3::FLOW3::Validation::Validator::Chain', $argument->getValidator(), 'The returned validator is not a chain as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorChainAddsThePassedValidatorsToTheCreatedValidatorChain() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy');
		$argument->setNewValidatorChain(array('F3::FLOW3::Validation::Validator::Text', 'F3::FLOW3::Validation::Validator::EmailAddress'));

		$validatorChain = $argument->getValidator();
		$this->assertType('F3::FLOW3::Validation::Validator::Text', $validatorChain->getValidator(0), 'The returned validator is not a text validator as expected.');
		$this->assertType('F3::FLOW3::Validation::Validator::EmailAddress', $validatorChain->getValidator(1), 'The returned validator is not a email validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorChainCanHandleShortValidatorNames() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy');
		$argument->setNewValidatorChain(array('Text', 'EmailAddress'));

		$validatorChain = $argument->getValidator();
		$this->assertType('F3::FLOW3::Validation::Validator::Text', $validatorChain->getValidator(0), 'The returned validator is not a text validator as expected.');
		$this->assertType('F3::FLOW3::Validation::Validator::EmailAddress', $validatorChain->getValidator(1), 'The returned validator is not a email validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChainCreatesANewFilterChainObject() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy');
		$argument->setNewFilterChain(array('F3::FLOW3::Validation::Filter::Chain', 'F3::FLOW3::Validation::Filter::Chain'));

		$this->assertType('F3::FLOW3::Validation::Filter::Chain', $argument->getFilter(), 'The returned filter is not a chain as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChainAddsThePassedFiltersToTheCreatedFilterChain() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy');
		$argument->setNewFilterChain(array('F3::FLOW3::Validation::Filter::Chain', 'F3::FLOW3::Validation::Filter::Chain'));

		$filterChain = $argument->getFilter();
		$this->assertType('F3::FLOW3::Validation::Filter::Chain', $filterChain->getFilter(0), 'The returned filter is not a filter chain as expected.');
		$this->assertType('F3::FLOW3::Validation::Filter::Chain', $filterChain->getFilter(1), 'The returned filter is not a filter chain as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChainCanHandleShortFilterNames() {
		$argument = $this->componentFactory->getComponent('F3::FLOW3::MVC::Controller::Argument', 'dummy');
		$argument->setNewFilterChain(array('Chain', 'Chain'));

		$filterChain = $argument->getFilter();
		$this->assertType('F3::FLOW3::Validation::Filter::Chain', $filterChain->getFilter(0), 'The returned filter is not a filter chain as expected.');
		$this->assertType('F3::FLOW3::Validation::Filter::Chain', $filterChain->getFilter(1), 'The returned filter is not a filter chain as expected.');
	}
}
?>