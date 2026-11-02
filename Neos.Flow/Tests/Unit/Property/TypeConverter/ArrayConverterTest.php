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
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\ArrayConverter;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Array converter
 */
final class ArrayConverterTest extends UnitTestCase
{
    /**
     * @var ArrayConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new ArrayConverter();
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['array', 'string', PersistentResource::class], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    #[Test]
    public function convertFromDoesNotModifyTheSourceArray()
    {
        $sourceArray = ['Foo' => 'Bar', 'Baz'];
        self::assertEquals($sourceArray, $this->converter->convertFrom($sourceArray, 'array'));
    }

    public static function stringToArrayDataProvider(): \Iterator
    {
        yield ['Foo,Bar,Baz', ['Foo', 'Bar', 'Baz'], []];
        yield ['Foo, Bar, Baz', ['Foo', 'Bar', 'Baz'], [ArrayConverter::CONFIGURATION_STRING_DELIMITER => ', ']];
        yield ['', [], []];
        yield ['[1,2,"foo"]', [1,2, 'foo'], [ArrayConverter::CONFIGURATION_STRING_FORMAT => ArrayConverter::STRING_FORMAT_JSON]];
    }

    #[DataProvider('stringToArrayDataProvider')]
    #[Test]
    public function canConvertFromStringToArray($source, $expectedResult, $mappingConfiguration)
    {
        // Create a map of arguments to return values.
        $configurationValueMap = [];
        foreach ($mappingConfiguration as $setting => $value) {
            $configurationValueMap[] = [ArrayConverter::class, $setting, $value];
        }

        $propertyMappingConfiguration = $this->createMock(PropertyMappingConfiguration::class);
        $propertyMappingConfiguration
            ->method('getConfigurationValue')
            ->willReturnMap($configurationValueMap);

        self::assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', [], $propertyMappingConfiguration));
    }
}
