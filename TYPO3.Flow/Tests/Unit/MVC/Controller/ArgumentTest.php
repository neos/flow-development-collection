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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ArgumentTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
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
	public function setValueTriesToConvertAnUuidStringIntoTheRealObjectIfDataTypeClassSchemaIsAvailable() {
		$object = new \stdClass();

		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array() ,'', FALSE);
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with('e104e469-9030-4b98-babf-3990f07dd3f1')->will($this->returnValue($object));

		$argument = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\Argument', array('findObjectByIdentityUUID'), array(), '', FALSE);
		$argument->injectPersistenceManager($mockPersistenceManager);
		$argument->_set('dataTypeClassSchema', $mockClassSchema);
		$argument->_set('dataType', 'stdClass');
		$argument->setValue('e104e469-9030-4b98-babf-3990f07dd3f1');

		$this->assertSame($object, $argument->_get('value'));
		$this->assertSame(\F3\FLOW3\MVC\Controller\Argument::ORIGIN_PERSISTENCE, $argument->getOrigin());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueHandsArraysOverToThePropertyMapperIfDataTypeClassSchemaIsAvailable() {
		$object = new \stdClass();

		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array() ,'', FALSE);
		$mockPropertyMapper = $this->getMock('F3\FLOW3\Property\PropertyMapper');
		$mockPropertyMapper->expects($this->once())->method('map')->with(array('foo'), array('foo' => 'bar'), 'stdClass')->will($this->returnValue($object));

		$argument = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\Argument', array('dummy'), array(), '', FALSE);
		$argument->injectPropertyMapper($mockPropertyMapper);
		$argument->_set('dataTypeClassSchema', $mockClassSchema);
		$argument->_set('dataType', 'stdClass');
		$argument->setValue(array('foo' => 'bar'));

		$this->assertSame($object, $argument->_get('value'));
		$this->assertSame(\F3\FLOW3\MVC\Controller\Argument::ORIGIN_PERSISTENCE_AND_MODIFIED, $argument->getOrigin());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidArgumentValueException
	 */
	public function setValueThrowsExceptionIfValueIsNotInstanceOfDataType() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array() ,'', FALSE);
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->will($this->returnValue(new \stdClass()));

		$argument = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\Argument', array('findObjectByIdentityUUID'), array(), '', FALSE);
		$argument->injectPersistenceManager($mockPersistenceManager);
		$argument->_set('dataTypeClassSchema', $mockClassSchema);
		$argument->_set('dataType', 'ArrayObject');
		$argument->setValue('e104e469-9030-4b98-babf-3990f07dd3f1');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setValueTriesToMapObjectIfDataTypeClassSchemaIsNotSet() {
		$object = new \stdClass();
		$object->title = 'Hello';

		$mockPropertyMapper = $this->getMock('F3\FLOW3\Property\PropertyMapper');
		$mockPropertyMapper->expects($this->once())->method('map')->with(array('title'), array('title' => 'Hello'), 'stdClass')->will($this->returnValue($object));

		$argument = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\Argument', array('findObjectByIdentityUUID'), array(), '', FALSE);
		$argument->_set('dataType', 'stdClass');
		$argument->injectPropertyMapper($mockPropertyMapper);


		$argument->setValue(array('title' => 'Hello'));
		$this->assertSame($object, $argument->_get('value'));
		$this->assertSame(\F3\FLOW3\MVC\Controller\Argument::ORIGIN_NEWLY_CREATED, $argument->getOrigin());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidArgumentValueException
	 */
	public function setValueThrowsExceptionIfComplexObjectShouldBeGeneratedFromStringAndDataTypeClassSchemaIsNotSet() {
		$argument = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\Argument', array('findObjectByIdentityUUID'), array(), '', FALSE);
		$argument->_set('dataType', 'stdClass');

		$argument->setValue(42);
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setDefaultValueReallySetsDefaultValue() {
		$argument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'Text');
		$argument->injectObjectManager($this->mockObjectManager);
		$argument->setDefaultValue(42);

		$this->assertEquals(42, $argument->getValue(), 'The default value was not stored in the Argument.');
	}

}
?>