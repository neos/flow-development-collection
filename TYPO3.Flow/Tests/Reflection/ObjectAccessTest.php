<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Reflection;

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

require_once('Fixture/DummyClassWithGettersAndSetters.php');

/**
 * Testcase for Object Access
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ObjectAccessTest extends \F3\Testing\BaseTestCase {

	protected $dummyObject;

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->dummyObject = new \F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithGettersAndSetters();
		$this->dummyObject->setProperty('string1');
		$this->dummyObject->setAnotherProperty(42);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyReturnsExpectedValueForGetterProperty() {
		$property = \F3\FLOW3\Reflection\ObjectAccess::getProperty($this->dummyObject, 'property');
		$this->assertEquals($property, 'string1');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyReturnsExpectedValueForPublicProperty() {
		$property = \F3\FLOW3\Reflection\ObjectAccess::getProperty($this->dummyObject, 'publicProperty2');
		$this->assertEquals($property, 42, 'A property of a given object was not returned correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyReturnsNullIfPropertyDoesNotExist() {
		$property = \F3\FLOW3\Reflection\ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty');
		$this->assertNull($property);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyThrowsExceptionIfThePropertyNameIsNotAString() {
		$property = \F3\FLOW3\Reflection\ObjectAccess::getProperty($this->dummyObject, new \ArrayObject());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setPropertyThrowsExceptionIfThePropertyNameIsNotAString() {
		$property = \F3\FLOW3\Reflection\ObjectAccess::setProperty($this->dummyObject, new \ArrayObject(), 42);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPropertyReturnsFalseIfPropertyIsNotAccessible() {
		$this->assertFalse(\F3\FLOW3\Reflection\ObjectAccess::setProperty($this->dummyObject, 'protectedProperty', 42));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setPropertyCallsASetterMethodToSetThePropertyValueIfOneIsAvailable() {
		\F3\FLOW3\Reflection\ObjectAccess::setProperty($this->dummyObject, 'property', 4242);
		$this->assertEquals($this->dummyObject->getProperty(), 4242, 'setProperty does not work with setter.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setPropertyWorksWithPublicProperty() {
		\F3\FLOW3\Reflection\ObjectAccess::setProperty($this->dummyObject, 'publicProperty', 4242);
		$this->assertEquals($this->dummyObject->publicProperty, 4242, 'setProperty does not work with public property.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setPropertyCanDirectlySetValuesInAnArrayObject() {
		$arrayObject = new \ArrayObject();
		\F3\FLOW3\Reflection\ObjectAccess::setProperty($arrayObject, 'publicProperty', 4242);
		$this->assertEquals($arrayObject['publicProperty'], 4242, 'setProperty does not work with ArrayObject property.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyCanAccessPropertiesOfAnArrayObject() {
		$arrayObject = new \ArrayObject(array('key' => 'value'));
		$expected = \F3\FLOW3\Reflection\ObjectAccess::getProperty($arrayObject, 'key');
		$this->assertEquals($expected, 'value', 'getProperty does not work with ArrayObject property.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getAccessiblePropertyNamesReturnsAllPropertiesWhichAreAvailable() {
		$declaredPropertyNames = \F3\FLOW3\Reflection\ObjectAccess::getAccessiblePropertyNames($this->dummyObject);
		$expectedPropertyNames = array('anotherProperty', 'property', 'property2', 'publicProperty', 'publicProperty2');
		$this->assertEquals($declaredPropertyNames, $expectedPropertyNames, 'getAccessiblePropertyNames returns not all public properties.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getAccessiblePropertiesReturnsTheCorrectValuesForAllProperties() {
		$allProperties = \F3\FLOW3\Reflection\ObjectAccess::getAccessibleProperties($this->dummyObject);
		$expectedProperties = array(
			'anotherProperty' => 42,
			'property' => 'string1',
			'property2' => NULL,
			'publicProperty' => NULL,
			'publicProperty2' => 42);
		$this->assertEquals($allProperties, $expectedProperties, 'expectedProperties did not return the right values for the properties.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyPathCanRecursivelyGetPropertiesOfAnObject() {
		$alternativeObject = new \F3\FLOW3\Tests\Reflection\Fixture\DummyClassWithGettersAndSetters();
		$alternativeObject->setProperty('test');
		$this->dummyObject->setProperty2($alternativeObject);
		
		$expected = 'test';
		$actual = \F3\FLOW3\Reflection\ObjectAccess::getPropertyPath($this->dummyObject, 'property2.property');
		$this->assertEquals($expected, $actual);
	}

}
?>