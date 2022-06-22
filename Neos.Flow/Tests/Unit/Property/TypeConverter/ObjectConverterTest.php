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

    protected function setUp(): void
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
        self::assertEquals(['array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(0, $this->converter->getPriority(), 'Priority does not match');
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
            $this->mockReflectionService->expects(self::once())->method('isClassAnnotatedWith')->with('TheTargetType', Flow\Entity::class)->will(self::returnValue($isEntity));
        } else {
            $this->mockReflectionService->expects(self::atLeast(2))->method('isClassAnnotatedWith')->withConsecutive(['TheTargetType', Flow\Entity::class], ['TheTargetType', Flow\ValueObject::class])->willReturnOnConsecutiveCalls($isEntity, $isValueObject);
        }

        self::assertEquals($expected, $this->converter->canConvertFrom('myInputData', 'TheTargetType'));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType()
    {
        $this->mockReflectionService->expects(self::any())->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->will(self::returnValue(false));
        $this->mockReflectionService->expects(self::any())->method('getMethodParameters')->with('TheTargetType', '__construct')->will(self::returnValue([
            'thePropertyName' => [
                'type' => 'TheTypeOfSubObject',
                'elementType' => null
            ]
        ]));
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(ObjectConverter::class, []);
        self::assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldRemoveLeadingBackslashesForAnnotationParameters()
    {
        $this->mockReflectionService->expects(self::any())->method('getMethodParameters')->with('TheTargetType', '__construct')->will(self::returnValue([]));
        $this->mockReflectionService->expects(self::any())->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->will(self::returnValue(false));
        $this->mockReflectionService->expects(self::any())->method('getClassPropertyNames')->with('TheTargetType')->will(self::returnValue([
            'thePropertyName'
        ]));
        $this->mockReflectionService->expects(self::any())->method('getPropertyTagValues')->with('TheTargetType', 'thePropertyName')->will(self::returnValue([
            '\TheTypeOfSubObject'
        ]));
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(ObjectConverter::class, []);
        self::assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }
}
