<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * Testcase for the MVC Controller Argument
 *
 * @package		FLOW3
 * @version 	$Id:\F3\FLOW3\MVC\Controller\ArgumentsTest.php 201 2007-09-10 23:58:30Z Andi $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ArgumentTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $mockObjectFactory;

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$this->mockObjectManager->expects($this->any())->method('getObjectFactory')->will($this->returnValue($this->mockObjectFactory));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function constructingArgumentWithoutNameThrowsException() {
		new \F3\FLOW3\MVC\Controller\Argument(NULL, 'Text', $this->mockObjectManager);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructingArgumentWithInvalidNameThrowsException() {
		new \F3\FLOW3\MVC\Controller\Argument(new \ArrayObject(), 'Text', $this->mockObjectManager);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingDataTypeToConstructorReallySetsTheDataType() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Number', $this->mockObjectManager);
		$this->assertEquals('Number', $argument->getDataType(), 'The specified data type has not been set correctly.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortNameProvidesFluentInterface() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text', $this->mockObjectManager);
		$returnedArgument = $argument->setShortName('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValueProvidesFluentInterface() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text', $this->mockObjectManager);
		$returnedArgument = $argument->setValue('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortHelpMessageProvidesFluentInterface() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text', $this->mockObjectManager);
		$returnedArgument = $argument->setShortHelpMessage('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toStringReturnsTheStringVersionOfTheArgumentsValue() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text', $this->mockObjectManager);
		$argument->setValue(123);

		$this->assertSame((string)$argument, '123', 'The returned argument is not a string.');
		$this->assertNotSame((string)$argument, 123, 'The returned argument is identical to the set value.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dataTypeValidatorCanBeAFullClassname() {
		$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with('F3\FLOW3\Validation\Validator\Text')->will($this->returnValue(TRUE));
		$this->mockObjectManager->expects($this->any())->method('getObject')->with('F3\FLOW3\Validation\Validator\Text')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\Text')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('SomeArgument', 'F3\FLOW3\Validation\Validator\Text', $this->mockObjectManager);

		$this->assertType('F3\FLOW3\Validation\Validator\Text', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dataTypeValidatorCanBeAShortName() {
		$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with('Text')->will($this->returnValue(FALSE));
		$this->mockObjectManager->expects($this->any())->method('getObject')->with('F3\FLOW3\Validation\Validator\Text')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\Text')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('SomeArgument', 'Text', $this->mockObjectManager);

		$this->assertType('F3\FLOW3\Validation\Validator\Text', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function defaultDataTypeIsText() {
		$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with('Text')->will($this->returnValue(FALSE));
		$this->mockObjectManager->expects($this->any())->method('getObject')->with('F3\FLOW3\Validation\Validator\Text')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\Text')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('SomeArgument', NULL, $this->mockObjectManager);

		$this->assertType('F3\FLOW3\Validation\Validator\Text', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorChainCreatesANewValidatorChainObject() {
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\Chain')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\Chain')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text', $this->mockObjectManager);
		$argument->setNewValidatorChain(array());

		$this->assertType('F3\FLOW3\Validation\Validator\Chain', $argument->getValidator(), 'The returned validator is not a chain as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorChainAddsThePassedValidatorsToTheCreatedValidatorChain() {
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\Chain()));
		$this->mockObjectManager->expects($this->exactly(3))->method('isObjectRegistered')->will($this->onConsecutiveCalls(FALSE, TRUE, TRUE));
		$this->mockObjectManager->expects($this->at(2))->method('getObject')->with('F3\FLOW3\Validation\Validator\Text')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\Text()));
		$this->mockObjectManager->expects($this->at(4))->method('getObject')->with('F3\FLOW3\Validation\Validator\Text')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\Text()));
		$this->mockObjectManager->expects($this->at(6))->method('getObject')->with('F3\FLOW3\Validation\Validator\EmailAddress')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\EmailAddress()));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text', $this->mockObjectManager);
		$argument->setNewValidatorChain(array('F3\FLOW3\Validation\Validator\Text', 'F3\FLOW3\Validation\Validator\EmailAddress'));

		$validatorChain = $argument->getValidator();
		$this->assertType('F3\FLOW3\Validation\Validator\Text', $validatorChain->getValidator(0), 'The returned validator is not a text validator as expected.');
		$this->assertType('F3\FLOW3\Validation\Validator\EmailAddress', $validatorChain->getValidator(1), 'The returned validator is not a email validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorChainCanHandleShortValidatorNames() {
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\Chain()));
		$this->mockObjectManager->expects($this->exactly(3))->method('isObjectRegistered')->will($this->onConsecutiveCalls(FALSE));
		$this->mockObjectManager->expects($this->at(2))->method('getObject')->with('F3\FLOW3\Validation\Validator\Text')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\Text()));
		$this->mockObjectManager->expects($this->at(4))->method('getObject')->with('F3\FLOW3\Validation\Validator\Text')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\Text()));
		$this->mockObjectManager->expects($this->at(6))->method('getObject')->with('F3\FLOW3\Validation\Validator\EmailAddress')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\EmailAddress()));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text', $this->mockObjectManager);
		$argument->setNewValidatorChain(array('Text', 'EmailAddress'));

		$validatorChain = $argument->getValidator();
		$this->assertType('F3\FLOW3\Validation\Validator\Text', $validatorChain->getValidator(0), 'The returned validator is not a text validator as expected.');
		$this->assertType('F3\FLOW3\Validation\Validator\EmailAddress', $validatorChain->getValidator(1), 'The returned validator is not a email validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChainCreatesANewFilterChainObject() {
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Filter\Chain')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text', $this->mockObjectManager);
		$argument->setNewFilterChain(array());

		$this->assertType('F3\FLOW3\Validation\Filter\Chain', $argument->getFilter(), 'The returned filter is not a chain as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChainAddsThePassedFiltersToTheCreatedFilterChain() {
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));
		$this->mockObjectManager->expects($this->exactly(3))->method('isObjectRegistered')->will($this->onConsecutiveCalls(FALSE, TRUE, TRUE));
		$this->mockObjectManager->expects($this->at(2))->method('getObject')->with('F3\FLOW3\Validation\Validator\Text')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\Text()));
		$this->mockObjectManager->expects($this->at(4))->method('getObject')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));
		$this->mockObjectManager->expects($this->at(6))->method('getObject')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text', $this->mockObjectManager);
		$argument->setNewFilterChain(array('F3\FLOW3\Validation\Filter\Chain', 'F3\FLOW3\Validation\Filter\Chain'));

		$filterChain = $argument->getFilter();
		$this->assertType('F3\FLOW3\Validation\Filter\Chain', $filterChain->getFilter(0), 'The returned filter is not a filter chain as expected.');
		$this->assertType('F3\FLOW3\Validation\Filter\Chain', $filterChain->getFilter(1), 'The returned filter is not a filter chain as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChainCanHandleShortFilterNames() {
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));
		$this->mockObjectManager->expects($this->exactly(3))->method('isObjectRegistered')->will($this->returnValue(FALSE));
		$this->mockObjectManager->expects($this->at(2))->method('getObject')->with('F3\FLOW3\Validation\Validator\Text')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\Text()));
		$this->mockObjectManager->expects($this->at(4))->method('getObject')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));
		$this->mockObjectManager->expects($this->at(6))->method('getObject')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text', $this->mockObjectManager);
		$argument->setNewFilterChain(array('Chain', 'Chain'));

		$filterChain = $argument->getFilter();
		$this->assertType('F3\FLOW3\Validation\Filter\Chain', $filterChain->getFilter(0), 'The returned filter is not a filter chain as expected.');
		$this->assertType('F3\FLOW3\Validation\Filter\Chain', $filterChain->getFilter(1), 'The returned filter is not a filter chain as expected.');
	}
}
?>