<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Schema Generator
 *
 */
class SchemaGeneratorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Utility\SchemaGenerator
	 */
	private $configurationGenerator;

	public function setUp() {
		$this->configurationGenerator = $this->getAccessibleMock('TYPO3\Flow\Utility\SchemaGenerator', array('getError'));
	}

	/**
	 * @return array
	 */
	public function schemaGenerationForSimpleTypesDataProvider() {
		return array(
			array('string', array('type' => 'string')),
			array(FALSE, array('type' => 'boolean')),
			array(TRUE, array('type' => 'boolean')),
			array(10.75, array('type' => 'number')),
			array(1234, array('type' => 'integer')),
			array(NULL, array('type' => 'null'))
		);
	}

	/**
	 * @dataProvider schemaGenerationForSimpleTypesDataProvider
	 * @test
	 */
	public function testSchemaGenerationForSimpleTypes($value, $expectedSchema) {
		$schema = $this->configurationGenerator->generate($value);
		$this->assertEquals($schema, $expectedSchema);
	}

	/**
	 * @return array
	 */
	public function schemaGenerationForArrayOfTypesDataProvider() {
		return array(
			array(array('string'), array('type' => 'array', 'items' => array('type' => 'string'))),
			array(array('string', 'foo', 'bar'), array('type' => 'array', 'items' => array('type' => 'string'))),
			array(array('string', 'foo', 123),  array('type' => 'array', 'items' => array(array('type' => 'string'), array('type' => 'integer'))))
		);
	}

	/**
	 * @dataProvider schemaGenerationForArrayOfTypesDataProvider
	 * @test
	 */
	public function testSchemaGenerationForArrayOfTypes($value, $expectedSchema) {
		$schema = $this->configurationGenerator->generate($value);
		$this->assertEquals($schema, $expectedSchema);
	}
}
