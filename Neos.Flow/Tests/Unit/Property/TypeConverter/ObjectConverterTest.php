<?php

declare(strict_types=1);

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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\ObjectConverter;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the ObjectConverter
 */
#[CoversClass('\Neos\Flow\Property\TypeConverter\ObjectConverter<extended>::class')]
final class ObjectConverterTest extends UnitTestCase
{
    /**
     * @var ObjectConverter
     */
    protected $converter;

    /**
     * @var ReflectionService
     */
    protected $mockReflectionService;

    protected function setUp(): void
    {
        $this->mockReflectionService = $this->createMock(ReflectionService::class);

        $this->converter = new ObjectConverter();
        $this->inject($this->converter, 'reflectionService', $this->mockReflectionService);
        $this->inject($this->converter, 'objectManager', $this->createStub(ObjectManagerInterface::class));
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(0, $this->converter->getPriority(), 'Priority does not match');
    }

    public static function dataProviderForCanConvert(): \Iterator
    {
        yield [true, false, false];
        // is entity => cannot convert
        yield [false, true, false];
        // is valueobject => cannot convert
        yield [false, false, true];
    }

    #[DataProvider('dataProviderForCanConvert')]
    #[Test]
    public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject(bool $isEntity, bool $isValueObject, bool $expected): void
    {
        $this->mockReflectionService->method('isClassAnnotatedWith')->willReturnCallback(
            function ($source, $targetType) use ($isEntity, $isValueObject): bool {
                if ($targetType === Flow\Entity::class) {
                    return $isEntity;
                }
                if ($targetType === Flow\ValueObject::class) {
                    return $isValueObject;
                }
                return false;
            }
        );
        self::assertSame($expected, $this->converter->canConvertFrom('myInputData', 'TheTargetType'));
    }

    #[Test]
    public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType()
    {
        $this->mockReflectionService->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->willReturn((false));
        $this->mockReflectionService->method('getMethodParameters')->with('TheTargetType', '__construct')->willReturn(([
            'thePropertyName' => [
                'type' => 'TheTypeOfSubObject',
                'elementType' => null
            ]
        ]));
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(ObjectConverter::class, []);
        self::assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }

    #[Test]
    public function getTypeOfChildPropertyShouldRemoveLeadingBackslashesForAnnotationParameters()
    {
        $this->mockReflectionService->method('getMethodParameters')->with('TheTargetType', '__construct')->willReturn(([]));
        $this->mockReflectionService->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->willReturn((false));
        $this->mockReflectionService->method('getClassPropertyNames')->with('TheTargetType')->willReturn(([
            'thePropertyName'
        ]));
        $this->mockReflectionService->method('getPropertyTagValues')->with('TheTargetType', 'thePropertyName')->willReturn(([
            '\TheTypeOfSubObject'
        ]));
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(ObjectConverter::class, []);
        self::assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }
}
