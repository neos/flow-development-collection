<?php
namespace TYPO3\Flow\Tests\Functional\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\ObjectConverter;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 */
class ObjectConverterTest extends FunctionalTestCase {

	/**
	 * @var ObjectConverter
	 */
	protected $converter;

	public function setUp() {
		parent::setUp();
		$this->converter = $this->objectManager->get('TYPO3\Flow\Property\TypeConverter\ObjectConverter');
	}

	/**
	 * @test
	 */
	public function getTypeOfChildPropertyImmediatelyReturnsConfiguredTargetTypeIfSetSo() {
		$propertyName = 'somePropertyName';
		$expectedTargetType = 'someExpectedTargetType';
		$configuration = new PropertyMappingConfiguration();
		$configuration
			->forProperty($propertyName)
			->setTypeConverterOption(
				'TYPO3\Flow\Property\TypeConverter\ObjectConverter',
				ObjectConverter::CONFIGURATION_TARGET_TYPE,
				$expectedTargetType);

		$actual = $this->converter->getTypeOfChildProperty('irrelevant', $propertyName, $configuration);
		$this->assertEquals($expectedTargetType, $actual);
	}

	/**
	 * @test
	 */
	public function getTypeOfChildPropertyReturnsCorrectTypeIfAConstructorArgumentForThatPropertyIsPresent() {
		$actual = $this->converter->getTypeOfChildProperty(
			'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestClass',
			'dummy',
			new PropertyMappingConfiguration()
		);
		$this->assertEquals('float', $actual);
	}

	/**
	 * @test
	 */
	public function getTypeOfChildPropertyReturnsCorrectTypeIfASetterForThatPropertyIsPresent() {
		$actual = $this->converter->getTypeOfChildProperty(
			'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestClass',
			'attributeWithStringTypeAnnotation',
			new PropertyMappingConfiguration()
		);
		$this->assertEquals('string', $actual);
	}

	/**
	 * @test
	 */
	public function getTypeOfChildPropertyThrowsExceptionIfThatPropertyIsPubliclyPresentButHasNoProperTypeAnnotation() {
		$this->setExpectedException('TYPO3\Flow\Property\Exception\InvalidTargetException', '', 1406821818);
		$this->converter->getTypeOfChildProperty(
			'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestClass',
			'somePublicPropertyWithoutVarAnnotation',
			new PropertyMappingConfiguration()
		);
	}

	/**
	 * @test
	 */
	public function getTypeOfChildPropertyReturnsCorrectTypeIfThatPropertyIsPubliclyPresent() {
		$configuration = new PropertyMappingConfiguration();
		$actual = $this->converter->getTypeOfChildProperty(
			'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestClass',
			'somePublicProperty',
			$configuration
		);
		$this->assertEquals('float', $actual);
	}

	/**
	 * @test
	 */
	public function convertFromUsesAppropriatePropertyPopulationMethodsInOrderConstructorSetterPublic() {
		$convertedObject = $this->converter->convertFrom(
			'irrelevant',
			'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestClass',
			array(
				'propertyMeantForConstructorUsage' => 'theValue',
				'propertyMeantForSetterUsage' => 'theValue',
				'propertyMeantForPublicUsage' => 'theValue'
			),
			new PropertyMappingConfiguration()
		);

		$this->assertEquals('theValue set via Constructor', ObjectAccess::getProperty($convertedObject, 'propertyMeantForConstructorUsage', TRUE));
		$this->assertEquals('theValue set via Setter', ObjectAccess::getProperty($convertedObject, 'propertyMeantForSetterUsage', TRUE));
		$this->assertEquals('theValue', ObjectAccess::getProperty($convertedObject, 'propertyMeantForPublicUsage', TRUE));
	}
}
