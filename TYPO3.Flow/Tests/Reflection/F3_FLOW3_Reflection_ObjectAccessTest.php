<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Reflection;

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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyClassWithGettersAndSetters.php');

/**
 * Testcase for Object Access
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\AOP\Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
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
		$this->assertEquals($property, 'string1', 'A property of a given object was not returned correctly.');
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
	 * @expectedException F3\FLOW3\Reflection\Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyThrowsExceptionIfPropertyDoesNotExist() {
		$property = \F3\FLOW3\Reflection\ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty');
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Reflection\Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyThrowsExceptionIfPropertyIsNoString() {
		$property = \F3\FLOW3\Reflection\ObjectAccess::getProperty($this->dummyObject, new \ArrayObject());
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Reflection\Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setPropertyThrowsExceptionIfPropertyIsNoString() {
		$property = \F3\FLOW3\Reflection\ObjectAccess::setProperty($this->dummyObject, new \ArrayObject(), 42);
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Reflection\Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setPropertyThrowsExceptionIfPropertyIsNotAccessible() {
		$property = \F3\FLOW3\Reflection\ObjectAccess::setProperty($this->dummyObject, 'protectedProperty', 42);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setPropertyWorksWhenSetterAvailable() {
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
	public function setPropertyWorksWithArrayObject() {
		$arrayObject = new \ArrayObject();
		\F3\FLOW3\Reflection\ObjectAccess::setProperty($arrayObject, 'publicProperty', 4242);
		$this->assertEquals($arrayObject['publicProperty'], 4242, 'setProperty does not work with ArrayObject property.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPropertyWorksWithArrayObject() {
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
		$expectedPropertyNames = array('anotherProperty', 'property', 'publicProperty', 'publicProperty2');
		$this->assertEquals($declaredPropertyNames, $expectedPropertyNames, 'getAccessiblePropertyNames returns not all public properties.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getAllPropertiesReturnsTheCorrectValuesForAllProperties() {
		$allProperties = \F3\FLOW3\Reflection\ObjectAccess::getAllProperties($this->dummyObject);
		$expectedProperties = array(
			'anotherProperty' => 42,
			'property' => 'string1',
			'publicProperty' => NULL,
			'publicProperty2' => 42);
		$this->assertEquals($allProperties, $expectedProperties, 'expectedProperties did not return the right values for the properties.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function isPropertyAccessibleReturnsCorrectResultForGetters() {
		$this->assertTrue(\F3\FLOW3\Reflection\ObjectAccess::isPropertyAccessible($this->dummyObject, 'property', \F3\FLOW3\Reflection\ObjectAccess::ACCESS_GET), 'IsPropertyAccessible returned wrong result when called on a public getter.');
		$this->assertFalse(\F3\FLOW3\Reflection\ObjectAccess::isPropertyAccessible($this->dummyObject, 'protectedProperty', \F3\FLOW3\Reflection\ObjectAccess::ACCESS_GET), 'IsPropertyAccessible returned wrong result when called on a protected getter.');
		$this->assertFalse(\F3\FLOW3\Reflection\ObjectAccess::isPropertyAccessible($this->dummyObject, 'privateProperty', \F3\FLOW3\Reflection\ObjectAccess::ACCESS_GET), 'IsPropertyAccessible returned wrong result when called on a private getter.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function isPropertyAccessibleReturnsCorrectResultForSetters() {
		$this->assertTrue(\F3\FLOW3\Reflection\ObjectAccess::isPropertyAccessible($this->dummyObject, 'property', \F3\FLOW3\Reflection\ObjectAccess::ACCESS_SET), 'IsPropertyAccessible returned wrong result when called on a public setter.');
		$this->assertFalse(\F3\FLOW3\Reflection\ObjectAccess::isPropertyAccessible($this->dummyObject, 'protectedProperty', \F3\FLOW3\Reflection\ObjectAccess::ACCESS_SET), 'IsPropertyAccessible returned wrong result when called on a protected setter.');
		$this->assertFalse(\F3\FLOW3\Reflection\ObjectAccess::isPropertyAccessible($this->dummyObject, 'privateProperty', \F3\FLOW3\Reflection\ObjectAccess::ACCESS_SET), 'IsPropertyAccessible returned wrong result when called on a private setter.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function isPropertyAccessibleReturnsCorrectResultForPublic() {
		$this->assertTrue(\F3\FLOW3\Reflection\ObjectAccess::isPropertyAccessible($this->dummyObject, 'publicProperty', \F3\FLOW3\Reflection\ObjectAccess::ACCESS_PUBLIC), 'IsPropertyAccessible returned wrong result when called on a public variable.');
		$this->assertFalse(\F3\FLOW3\Reflection\ObjectAccess::isPropertyAccessible($this->dummyObject, 'property', \F3\FLOW3\Reflection\ObjectAccess::ACCESS_PUBLIC), 'IsPropertyAccessible returned wrong result when called on a protected variable.');
		$this->assertFalse(\F3\FLOW3\Reflection\ObjectAccess::isPropertyAccessible($this->dummyObject, 'nonExistantProperty', \F3\FLOW3\Reflection\ObjectAccess::ACCESS_PUBLIC), 'IsPropertyAccessible returned wrong result when called on a private variable.');
	}
}
?>