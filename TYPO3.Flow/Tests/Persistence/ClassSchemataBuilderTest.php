<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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
 * @subpackage Persistence
 * @version $Id$
 */

require_once('Fixture/Entity1.php');
require_once('Fixture/ValueObject1.php');

/**
 * Testcase for the Class Schema Builder
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassSchemataBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Persistence\ClassSchemataBuilder
	 */
	protected $builder;

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->reflectionService = new \F3\FLOW3\Reflection\Service();
		$this->reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$this->reflectionService->initialize(
			array('F3\FLOW3\Tests\Persistence\Fixture\Entity1', 'F3\FLOW3\Tests\Persistence\Fixture\ValueObject1')
		);
		$this->builder = new \F3\FLOW3\Persistence\ClassSchemataBuilder($this->reflectionService);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classSchemaOnlyContainsNonTransientProperties() {
		$expectedProperties = array('someString', 'someInteger', 'someFloat', 'someDate', 'someBoolean', 'someIdentifier', 'someSplObjectStorage');

		$builtClassSchemata = $this->builder->build(array('F3\FLOW3\Tests\Persistence\Fixture\Entity1'));
		$builtClassSchema = array_pop($builtClassSchemata);
		$actualProperties = array_keys($builtClassSchema->getProperties());
		sort($expectedProperties);
		sort($actualProperties);
		$this->assertEquals($expectedProperties, $actualProperties);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function propertyTypesAreDetectedFromVarAnnotations() {
		$expectedProperties = array(
			'someBoolean' => 'boolean',
			'someString' => 'string',
			'someInteger' => 'integer',
			'someFloat' => 'float',
			'someDate' => 'DateTime',
			'someSplObjectStorage' => 'SplObjectStorage',
			'someIdentifier' => 'string'
		);

		$builtClassSchemata = $this->builder->build(array('F3\FLOW3\Tests\Persistence\Fixture\Entity1'));
		$builtClassSchema = array_pop($builtClassSchemata);
		$actualProperties = $builtClassSchema->getProperties();
		asort($expectedProperties);
		asort($actualProperties);
		$this->assertEquals($expectedProperties, $actualProperties);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function modelTypeEntityIsRecognizedByEntityAnnotation() {
		$builtClassSchemata = $this->builder->build(array('F3\FLOW3\Tests\Persistence\Fixture\Entity1'));
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getModelType(), \F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function modelTypeValueObjectIsRecognizedByValueObjectAnnotation() {
		$builtClassSchemata = $this->builder->build(array('F3\FLOW3\Tests\Persistence\Fixture\ValueObject1'));
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getModelType(), \F3\FLOW3\Persistence\ClassSchema::MODELTYPE_VALUEOBJECT);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classSchemaContainsNameOfItsRelatedClass() {
		$builtClassSchemata = $this->builder->build(array('F3\FLOW3\Tests\Persistence\Fixture\Entity1'));
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getClassName(), 'F3\FLOW3\Tests\Persistence\Fixture\Entity1');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function uuidPropertyNameIsDetectedFromAnnotation() {
		$builtClassSchemata = $this->builder->build(array('F3\FLOW3\Tests\Persistence\Fixture\Entity1'));
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getUUIDPropertyName(), 'someIdentifier');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function uuidPropertyNameIsSetAsRegularPropertyAsWell() {
		$builtClassSchemata = $this->builder->build(array('F3\FLOW3\Tests\Persistence\Fixture\Entity1'));
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertTrue(array_key_exists('someIdentifier', $builtClassSchema->getProperties()));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function identityPropertiesAreDetectedFromAnnotation() {
		$expectedIdentityProperties = array(
			'someString' => 'string',
			'someDate' => 'DateTime'
		);
		$builtClassSchemata = $this->builder->build(array('F3\FLOW3\Tests\Persistence\Fixture\Entity1'));
		$builtClassSchema = array_pop($builtClassSchemata);

		$this->assertSame($builtClassSchema->getIdentityProperties(), $expectedIdentityProperties);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function aggregateRootIsDetectedForEntities() {
		$reflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('isClassReflected'));
		$reflectionService->expects($this->at(0))->method('isClassReflected')->with('F3\FLOW3\Tests\Persistence\Fixture\Entity1')->will($this->returnValue(TRUE));
		$reflectionService->expects($this->at(1))->method('isClassReflected')->with('F3\FLOW3\Tests\Persistence\Fixture\Entity1Repository')->will($this->returnValue(TRUE));
		$reflectionService->setCache($this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE));
		$reflectionService->initialize(
			array('F3\FLOW3\Tests\Persistence\Fixture\Entity1', 'F3\FLOW3\Tests\Persistence\Fixture\ValueObject1')
		);
		$builder = new \F3\FLOW3\Persistence\ClassSchemataBuilder($reflectionService);

		$builtClassSchemata = $builder->build(array('F3\FLOW3\Tests\Persistence\Fixture\Entity1'));
		$builtClassSchema = array_pop($builtClassSchemata);
		$this->assertEquals($builtClassSchema->getModelType(), \F3\FLOW3\Persistence\ClassSchema::MODELTYPE_ENTITY);
		$this->assertTrue($builtClassSchema->isAggregateRoot());
	}

}

?>