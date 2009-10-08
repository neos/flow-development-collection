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

/**
 * Testcase for the Class Schema.
 *
 * Note that many parts of the class schema functionality are already tested by the class
 * schema builder testcase.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassSchemaTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasPropertyReturnsTrueOnlyForExistingProperties() {
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('SomeClass');
		$classSchema->addProperty('a', 'string');
		$classSchema->addProperty('b', 'integer');

		$this->assertTrue($classSchema->hasProperty('a'));
		$this->assertTrue($classSchema->hasProperty('b'));
		$this->assertFalse($classSchema->hasProperty('c'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPropertiesReturnsAddedProperties() {
		$expectedProperties = array(
			'a' => array('type' => 'string', 'elementType' => NULL, 'lazy' => FALSE),
			'b' => array('type' => 'F3\FLOW3\SomeObject', 'elementType' => NULL, 'lazy' => TRUE)
		);

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('SomeClass');
		$classSchema->addProperty('a', 'string');
		$classSchema->addProperty('b', 'F3\FLOW3\SomeObject', TRUE);

		$this->assertSame($expectedProperties, $classSchema->getProperties());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function markAsIdentityPropertyRejectsUnknownProperties() {
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('SomeClass');

		$classSchema->markAsIdentityProperty('unknownProperty');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function markAsIdentityPropertyRejectsLazyLoadedProperties() {
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('SomeClass');
		$classSchema->addProperty('lazyProperty', 'F3\FLOW3\SomeObject', TRUE);

		$classSchema->markAsIdentityProperty('lazyProperty');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentityPropertiesReturnsNamesAndTypes() {
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('SomeClass');
		$classSchema->addProperty('a', 'string');
		$classSchema->addProperty('b', 'integer');

		$classSchema->markAsIdentityProperty('a');

		$this->assertSame(array('a' => 'string'), $classSchema->getIdentityProperties());
	}

	/**
	 * data provider for addPropertyAcceptsValidPropertyTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validPropertyTypes() {
		return array(
			array('integer'),
			array('int'),
			array('float'),
			array('boolean'),
			array('string'),
			array('DateTime'),
			array('array'),
			array('ArrayObject'),
			array('SplObjectStorage'),
			array('F3\FLOW3\Foo'),
			array('\F3\FLOW3\Bar'),
			array('array<string>'),
			array('array<F3\FLOW3\Baz>')
		);
	}

	/**
	 * @dataProvider validPropertyTypes()
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addPropertyAcceptsValidPropertyTypes($propertyType) {
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('SomeClass');
		$classSchema->addProperty('a', $propertyType);
	}

	/**
	 * data provider for addPropertyRejectsInvalidPropertyTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function invalidPropertyTypes() {
		return array(
			array('stdClass'),
			array('\SomeObject'),
			array('string<string>'),
			array('int<F3\FLOW3\Baz>')
		);
	}
	/**
	 * @dataProvider invalidPropertyTypes()
	 * @test
	 * @expectedException \F3\FLOW3\Reflection\Exception\InvalidPropertyType
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addPropertyRejectsInvalidPropertyTypes($propertyType) {
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('SomeClass');
		$classSchema->addProperty('a', $propertyType);
	}

	/**
	 * Collections are arrays, ArrayObject and SplObjectStorage
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addPropertyStoresElementTypesForCollectionProperties() {
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('SomeClass');
		$classSchema->addProperty('a', 'array<\F3\FLOW3\Foo>');

		$properties = $classSchema->getProperties();
		$this->assertEquals('array', $properties['a']['type']);
		$this->assertEquals('\F3\FLOW3\Foo', $properties['a']['elementType']);
	}

}

?>