<?php
namespace Neos\Flow\Tests\Unit\Property\TypeConverter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\ObjectConverter;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Annotations as Flow;

/**
 * Testcase for the ObjectConverter
 *
 * @covers \Neos\Flow\Property\TypeConverter\ObjectConverter<extended>
 */
class ObjectConverterTest extends UnitTestCase
{
    /**
     * @var ObjectConverter
     */
    protected $converter;

    /**
     * @var ReflectionService
     */
    protected $mockReflectionService;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    public function setUp()
    {
        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);

        $this->converter = new ObjectConverter();
        $this->inject($this->converter, 'reflectionService', $this->mockReflectionService);
        $this->inject($this->converter, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(0, $this->converter->getPriority(), 'Priority does not match');
    }

    public function dataProviderForCanConvert()
    {
        return [
            [true, false, false], // is entity => cannot convert
            [false, true, false], // is valueobject => cannot convert
            [false, false, true] // is no entity and no value object => can convert
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForCanConvert
     */
    public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject($isEntity, $isValueObject, $expected)
    {
        if ($isEntity) {
            $this->mockReflectionService->expects($this->once())->method('isClassAnnotatedWith')->with('TheTargetType', Flow\Entity::class)->will($this->returnValue($isEntity));
        } else {
            $this->mockReflectionService->expects($this->at(0))->method('isClassAnnotatedWith')->with('TheTargetType', Flow\Entity::class)->will($this->returnValue($isEntity));
            $this->mockReflectionService->expects($this->at(1))->method('isClassAnnotatedWith')->with('TheTargetType', Flow\ValueObject::class)->will($this->returnValue($isValueObject));
        }

        $this->assertEquals($expected, $this->converter->canConvertFrom('myInputData', 'TheTargetType'));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType()
    {
        $this->mockReflectionService->expects($this->any())->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->will($this->returnValue(false));
        $this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with('TheTargetType', '__construct')->will($this->returnValue([
            'thePropertyName' => [
                'type' => 'TheTypeOfSubObject',
                'elementType' => null
            ]
        ]));
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(ObjectConverter::class, []);
        $this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldRemoveLeadingBackslashesForAnnotationParameters()
    {
        $this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with('TheTargetType', '__construct')->will($this->returnValue([]));
        $this->mockReflectionService->expects($this->any())->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->will($this->returnValue(false));
        $this->mockReflectionService->expects($this->any())->method('getClassPropertyNames')->with('TheTargetType')->will($this->returnValue([
            'thePropertyName'
        ]));
        $this->mockReflectionService->expects($this->any())->method('getPropertyTagValues')->with('TheTargetType', 'thePropertyName')->will($this->returnValue([
            '\TheTypeOfSubObject'
        ]));
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(ObjectConverter::class, []);
        $this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }
}
