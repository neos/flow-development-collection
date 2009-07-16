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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueTriesToConvertAnUUIDStringIntoTheRealObjectIfDataTypeIsAClassName() {
		$object = new \stdClass();

		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array() ,'', FALSE);

		$argument = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\Argument'), array('findObjectByIdentityUUID'), array(), '', FALSE);
		$argument->_set('dataTypeClassSchema', $mockClassSchema);
		$argument->expects($this->once())->method('findObjectByIdentityUUID')->with('e104e469-9030-4b98-babf-3990f07dd3f1')->will($this->returnValue($object));
		$argument->setValue('e104e469-9030-4b98-babf-3990f07dd3f1');

		$this->assertSame($object, $argument->_get('value'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValueTriesToConvertAnIdentityArrayContainingAUUIDIntoTheRealObject() {
		$object = new \stdClass();

		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array() ,'', FALSE);

		$argument = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\Argument'), array('findObjectByIdentityUUID'), array(), '', FALSE);
		$argument->_set('dataTypeClassSchema', $mockClassSchema);
		$argument->expects($this->once())->method('findObjectByIdentityUUID')->with('e104e469-9030-4b98-babf-3990f07dd3f1')->will($this->returnValue($object));
		$argument->setValue(array('__identity' => 'e104e469-9030-4b98-babf-3990f07dd3f1'));

		$this->assertSame($object, $argument->_get('value'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValueTriesToConvertAnIdentityArrayContainingIdentifiersIntoTheRealObject() {
		$this->markTestIncomplete('Not yet fully implemented.');

		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array() ,'', FALSE);

		$mockQuery = $this->getMock('F3\TYPO3CR\FLOW3\Persistence\Query', array(), array(), '', FALSE);
		# TODO Insert more expectations here
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue(array('the object')));

		$mockQueryFactory = $this->getMock('F3\TYPO3CR\FLOW3\Persistence\QueryFactory', array(), array(), '', FALSE);
		$mockQueryFactory->expects($this->once())->method('create')->with('MyClass')->will($this->returnValue($mockQuery));

		$argument = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\Argument'), array('dummy'), array(), '', FALSE);
		$argument->_set('dataType', 'MyClass');
		$argument->_set('dataTypeClassSchema', $mockClassSchema);
		$argument->_set('queryFactory', $mockQueryFactory);
		$argument->setValue(array('__identity' => array('key1' => 'value1', 'key2' => 'value2')));

		$this->assertSame('the object', $argument->_get('value'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValueConvertsAnArrayIntoAFreshObjectWithThePropertiesSetToTheArrayValuesIfDataTypeIsAClassAndNoIdentityInformationIsFoundInTheValue() {
		$theValue = array('property1' => 'value1', 'property2' => 'value2');

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->with('MyClass')->will($this->returnValue('the object'));

		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array() ,'', FALSE);

		$mockPropertyMapper = $this->getMock('F3\FLOW3\Property\Mapper', array('map'), array(), '', FALSE);
		$mockPropertyMapper->expects($this->once())->method('map')->with(array('property1', 'property2'), $theValue, 'the object')->will($this->returnValue(TRUE));

		$argument = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\Argument'), array('dummy'), array(), '', FALSE);
		$argument->_set('dataType', 'MyClass');
		$argument->_set('dataTypeClassSchema', $mockClassSchema);
		$argument->_set('objectFactory', $mockObjectFactory);
		$argument->_set('propertyMapper', $mockPropertyMapper);
		$argument->setValue($theValue);

		$this->assertSame('the object', $argument->_get('value'));
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultDataTypeIsText() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('SomeArgument');
		$this->assertSame('Text', $argument->getDataType());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setNewValidatorConjunctionCreatesANewValidatorConjunctionObject() {
		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator')));

		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$argument->injectObjectFactory($this->mockObjectFactory);
		$argument->setNewValidatorConjunction(array());

		$this->assertType('F3\FLOW3\Validation\Validator\ConjunctionValidator', $argument->getValidator(), 'The returned validator is not a chain as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNewValidatorConjunctionAddsThePassedValidatorsToTheCreatedValidatorChain() {
		$mockValidator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$mockValidatorChain = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$mockValidatorChain->expects($this->at(0))->method('addValidator')->with($mockValidator1);
		$mockValidatorChain->expects($this->at(1))->method('addValidator')->with($mockValidator2);

		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockValidatorChain));

		$this->mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnValue(TRUE));
		$this->mockObjectManager->expects($this->exactly(2))->method('getObject')->will($this->onConsecutiveCalls($mockValidator1, $mockValidator2));

		$argument = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\Argument'), array('dummy'), array(), '', FALSE);
		$argument->_set('objectManager', $this->mockObjectManager);
		$argument->_set('objectFactory', $this->mockObjectFactory);

		$argument->setNewValidatorConjunction(array('Validator1', 'Validator2'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNewValidatorConjunctionCanHandleShortValidatorNames() {
		$mockValidator1 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator2 = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$mockValidatorChain = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$mockValidatorChain->expects($this->at(0))->method('addValidator')->with($mockValidator1);
		$mockValidatorChain->expects($this->at(1))->method('addValidator')->with($mockValidator2);

		$this->mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\Validation\Validator\ConjunctionValidator')->will($this->returnValue($mockValidatorChain));

		$this->mockObjectManager->expects($this->any())->method('isObjectRegistered')->will($this->returnValue(FALSE));
		$this->mockObjectManager->expects($this->exactly(2))->method('getObject')->will($this->onConsecutiveCalls($mockValidator1, $mockValidator2));

		$argument = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\Argument'), array('dummy'), array(), '', FALSE);
		$argument->_set('objectManager', $this->mockObjectManager);
		$argument->_set('objectFactory', $this->mockObjectFactory);

		$argument->setNewValidatorConjunction(array('Validator1', 'Validator2'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setDefaultValueReallySetsDefaultValue() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$argument->injectObjectFactory($this->mockObjectFactory);
		$argument->setDefaultValue(42);

		$this->assertEquals(42, $argument->getValue(), 'The default value was not stored in the Argument.');
	}

}
?>