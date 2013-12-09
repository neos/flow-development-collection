<?php
namespace TYPO3\Flow\Tests\Unit\Property\TypeConverter;

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
 * Testcase for the ObjectConverter
 *
 * @covers \TYPO3\Flow\Property\TypeConverter\ObjectConverter<extended>
 */
class ObjectConverterTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Property\TypeConverter\ObjectConverter
	 */
	protected $converter;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	public function setUp() {
		$this->mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');

		$this->converter = new \TYPO3\Flow\Property\TypeConverter\ObjectConverter();
		$this->inject($this->converter, 'reflectionService', $this->mockReflectionService);
		$this->inject($this->converter, 'objectManager', $this->mockObjectManager);
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('array'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(0, $this->converter->getPriority(), 'Priority does not match');
	}

	public function dataProviderForCanConvert() {
		return array(
			array(TRUE, FALSE, FALSE), // is entity => cannot convert
			array(FALSE, TRUE, FALSE), // is valueobject => cannot convert
			array(FALSE, FALSE, TRUE) // is no entity and no value object => can convert
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderForCanConvert
	 */
	public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject($isEntity, $isValueObject, $expected) {
		if ($isEntity) {
			$this->mockReflectionService->expects($this->once())->method('isClassAnnotatedWith')->with('TheTargetType', 'TYPO3\Flow\Annotations\Entity')->will($this->returnValue($isEntity));
		} else {
			$this->mockReflectionService->expects($this->at(0))->method('isClassAnnotatedWith')->with('TheTargetType', 'TYPO3\Flow\Annotations\Entity')->will($this->returnValue($isEntity));
			$this->mockReflectionService->expects($this->at(1))->method('isClassAnnotatedWith')->with('TheTargetType', 'TYPO3\Flow\Annotations\ValueObject')->will($this->returnValue($isValueObject));
		}

		$this->assertEquals($expected, $this->converter->canConvertFrom('myInputData', 'TheTargetType'));
	}

	/**
	 * @test
	 */
	public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType() {
		$this->mockReflectionService->expects($this->any())->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->will($this->returnValue(FALSE));
		$this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with('TheTargetType', '__construct')->will($this->returnValue(array(
			'thePropertyName' => array(
				'type' => 'TheTypeOfSubObject',
				'elementType' => NULL
			)
		)));
		$configuration = new \TYPO3\Flow\Property\PropertyMappingConfiguration();
		$configuration->setTypeConverterOptions('TYPO3\Flow\Property\TypeConverter\ObjectConverter', array());
		$this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
	}

}
