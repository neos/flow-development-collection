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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Testcase for the MVC Controller Argument
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
		new \F3\FLOW3\MVC\Controller\Argument(NULL, 'Text');
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructingArgumentWithInvalidNameThrowsException() {
		new \F3\FLOW3\MVC\Controller\Argument(new \ArrayObject(), 'Text');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingDataTypeToConstructorReallySetsTheDataType() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Number');
		$this->assertEquals('Number', $argument->getDataType(), 'The specified data type has not been set correctly.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortNameProvidesFluentInterface() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$returnedArgument = $argument->setShortName('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValueProvidesFluentInterface() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$returnedArgument = $argument->setValue('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortHelpMessageProvidesFluentInterface() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$returnedArgument = $argument->setShortHelpMessage('x');
		$this->assertSame($argument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toStringReturnsTheStringVersionOfTheArgumentsValue() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$argument->setValue(123);

		$this->assertSame((string)$argument, '123', 'The returned argument is not a string.');
		$this->assertNotSame((string)$argument, 123, 'The returned argument is identical to the set value.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dataTypeValidatorCanBeAFullClassName() {
		$this->markTestIncomplete();

		$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue(TRUE));
		$this->mockObjectManager->expects($this->any())->method('getObject')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\TextValidator')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('SomeArgument', 'F3\FLOW3\Validation\Validator\TextValidator');
		$argument->injectObjectManager($this->mockObjectManager);

		$this->assertType('F3\FLOW3\Validation\Validator\TextValidator', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dataTypeValidatorCanBeAShortName() {
		$this->markTestIncomplete();

		$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue(TRUE));
		$this->mockObjectManager->expects($this->any())->method('getObject')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\TextValidator')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('SomeArgument', 'Text');
		$argument->injectObjectManager($this->mockObjectManager);

		$this->assertType('F3\FLOW3\Validation\Validator\TextValidator', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultDataTypeIsText() {
		$this->markTestIncomplete();

		$this->mockObjectManager->expects($this->once())->method('isObjectRegistered')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue(TRUE));
		$this->mockObjectManager->expects($this->any())->method('getObject')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\TextValidator')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('SomeArgument', NULL);
		$argument->injectObjectManager($this->mockObjectManager);

		$this->assertType('F3\FLOW3\Validation\Validator\TextValidator', $argument->getDatatypeValidator(), 'The returned datatype validator is not a text validator as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorChainCreatesANewValidatorChainObject() {
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\ChainValidator')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\ChainValidator')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$argument->injectObjectManager($this->mockObjectManager);
		$argument->setNewValidatorChain(array());

		$this->assertType('F3\FLOW3\Validation\Validator\ChainValidator', $argument->getValidator(), 'The returned validator is not a chain as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNewValidatorChainAddsThePassedValidatorsToTheCreatedValidatorChain() {
		$mockValidator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$mockValidatorChain = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);
		$mockValidatorChain->expects($this->at(0))->method('addValidator')->with($mockValidator1);
		$mockValidatorChain->expects($this->at(1))->method('addValidator')->with($mockValidator2);

		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\ChainValidator')->will($this->returnValue($mockValidatorChain));

		$this->mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnValue(TRUE));
		$this->mockObjectManager->expects($this->exactly(2))->method('getObject')->will($this->onConsecutiveCalls($mockValidator1, $mockValidator2));

		$argument = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\Argument'), array('dummy'), array(), '', FALSE);
		$argument->_set('objectManager', $this->mockObjectManager);
		$argument->_set('objectFactory', $this->mockObjectFactory);

		$argument->setNewValidatorChain(array('Validator1', 'Validator2'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNewValidatorChainCanHandleShortValidatorNames() {
		$mockValidator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$mockValidatorChain = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);
		$mockValidatorChain->expects($this->at(0))->method('addValidator')->with($mockValidator1);
		$mockValidatorChain->expects($this->at(1))->method('addValidator')->with($mockValidator2);

		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\ChainValidator')->will($this->returnValue($mockValidatorChain));

		$this->mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnValue(FALSE));
		$this->mockObjectManager->expects($this->exactly(2))->method('getObject')->will($this->onConsecutiveCalls($mockValidator1, $mockValidator2));

		$argument = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\Argument'), array('dummy'), array(), '', FALSE);
		$argument->_set('objectManager', $this->mockObjectManager);
		$argument->_set('objectFactory', $this->mockObjectFactory);

		$argument->setNewValidatorChain(array('Validator1', 'Validator2'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChainCreatesANewFilterChainObject() {
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Filter\Chain')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$argument->injectObjectManager($this->mockObjectManager);
		$argument->setNewFilterChain(array());

		$this->assertType('F3\FLOW3\Validation\Filter\Chain', $argument->getFilter(), 'The returned filter is not a chain as expected.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewFilterChainAddsThePassedFiltersToTheCreatedFilterChain() {
		$this->markTestIncomplete();

		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));
		$this->mockObjectManager->expects($this->exactly(3))->method('isObjectRegistered')->will($this->onConsecutiveCalls(FALSE, TRUE, TRUE));
		$this->mockObjectManager->expects($this->at(2))->method('getObject')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\TextValidator()));
		$this->mockObjectManager->expects($this->at(4))->method('getObject')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));
		$this->mockObjectManager->expects($this->at(6))->method('getObject')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$argument->injectObjectManager($this->mockObjectManager);
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
		$this->markTestIncomplete();

		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));
		$this->mockObjectManager->expects($this->exactly(3))->method('isObjectRegistered')->will($this->returnValue(FALSE));
		$this->mockObjectManager->expects($this->at(2))->method('getObject')->with('F3\FLOW3\Validation\Validator\TextValidator')->will($this->returnValue(new \F3\FLOW3\Validation\Validator\TextValidator()));
		$this->mockObjectManager->expects($this->at(4))->method('getObject')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));
		$this->mockObjectManager->expects($this->at(6))->method('getObject')->with('F3\FLOW3\Validation\Filter\Chain')->will($this->returnValue(new \F3\FLOW3\Validation\Filter\Chain()));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$argument->injectObjectManager($this->mockObjectManager);
		$argument->setNewFilterChain(array('Chain', 'Chain'));

		$filterChain = $argument->getFilter();
		$this->assertType('F3\FLOW3\Validation\Filter\Chain', $filterChain->getFilter(0), 'The returned filter is not a filter chain as expected.');
		$this->assertType('F3\FLOW3\Validation\Filter\Chain', $filterChain->getFilter(1), 'The returned filter is not a filter chain as expected.');
	}
}
?>