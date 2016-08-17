<?php
namespace TYPO3\Flow\Tests\Unit\Property\TypeConverter;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the ObjectConverter
 *
 * @covers \TYPO3\Flow\Property\TypeConverter\ObjectConverter<extended>
 */
class ObjectConverterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
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

    public function setUp()
    {
        $this->mockReflectionService = $this->createMock(\TYPO3\Flow\Reflection\ReflectionService::class);
        $this->mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);

        $this->converter = new \TYPO3\Flow\Property\TypeConverter\ObjectConverter();
        $this->inject($this->converter, 'reflectionService', $this->mockReflectionService);
        $this->inject($this->converter, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(array('array'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(0, $this->converter->getPriority(), 'Priority does not match');
    }

    public function dataProviderForCanConvert()
    {
        return array(
            array(true, false, false), // is entity => cannot convert
            array(false, true, false), // is valueobject => cannot convert
            array(false, false, true) // is no entity and no value object => can convert
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForCanConvert
     */
    public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject($isEntity, $isValueObject, $expected)
    {
        if ($isEntity) {
            $this->mockReflectionService->expects($this->once())->method('isClassAnnotatedWith')->with('TheTargetType', \TYPO3\Flow\Annotations\Entity::class)->will($this->returnValue($isEntity));
        } else {
            $this->mockReflectionService->expects($this->at(0))->method('isClassAnnotatedWith')->with('TheTargetType', \TYPO3\Flow\Annotations\Entity::class)->will($this->returnValue($isEntity));
            $this->mockReflectionService->expects($this->at(1))->method('isClassAnnotatedWith')->with('TheTargetType', \TYPO3\Flow\Annotations\ValueObject::class)->will($this->returnValue($isValueObject));
        }

        $this->assertEquals($expected, $this->converter->canConvertFrom('myInputData', 'TheTargetType'));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType()
    {
        $this->mockReflectionService->expects($this->any())->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->will($this->returnValue(false));
        $this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with('TheTargetType', '__construct')->will($this->returnValue(array(
            'thePropertyName' => array(
                'type' => 'TheTypeOfSubObject',
                'elementType' => null
            )
        )));
        $configuration = new \TYPO3\Flow\Property\PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(\TYPO3\Flow\Property\TypeConverter\ObjectConverter::class, array());
        $this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldRemoveLeadingBackslashesForAnnotationParameters()
    {
        $this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with('TheTargetType', '__construct')->will($this->returnValue(array()));
        $this->mockReflectionService->expects($this->any())->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->will($this->returnValue(false));
        $this->mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with('TheTargetType')->will($this->returnValue(array(
            'thePropertyName'
        )));
        $this->mockReflectionService->expects($this->any())->method('getPropertyTagValues')->with('TheTargetType', 'thePropertyName')->will($this->returnValue(array(
            '\TheTypeOfSubObject'
        )));
        $configuration = new \TYPO3\Flow\Property\PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(\TYPO3\Flow\Property\TypeConverter\ObjectConverter::class, array());
        $this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }
}
