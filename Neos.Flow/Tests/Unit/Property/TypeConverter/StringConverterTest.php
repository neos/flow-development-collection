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
use Neos\Flow\Property\TypeConverter\StringConverter;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the String converter
 */
#[CoversClass('\Neos\Flow\Property\TypeConverter\StringConverter<extended>::class')]
final class StringConverterTest extends UnitTestCase
{
    /**
     * @var \Neos\Flow\Property\TypeConverterInterface
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new StringConverter();
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['string', 'integer', 'float', 'boolean', 'array', \DateTimeInterface::class], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('string', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    #[Test]
    public function convertFromShouldReturnSourceString()
    {
        self::assertEquals('myString', $this->converter->convertFrom('myString', 'string'));
    }

    #[Test]
    public function convertFromConvertsDateTimeObjects()
    {
        $date = new \DateTime('1980-12-13');
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverterOption(StringConverter::class, StringConverter::CONFIGURATION_DATE_FORMAT, 'd.m.Y');
        self::assertEquals('13.12.1980', $this->converter->convertFrom($date, 'string', [], $propertyMappingConfiguration));
    }

    #[Test]
    public function convertFromConvertsDateTimeImmutableObjects()
    {
        $date = new \DateTimeImmutable('1980-12-13');
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverterOption(StringConverter::class, StringConverter::CONFIGURATION_DATE_FORMAT, 'd.m.Y');
        self::assertEquals('13.12.1980', $this->converter->convertFrom($date, 'string', [], $propertyMappingConfiguration));
    }

    #[Test]
    public function canConvertFromShouldReturnTrue()
    {
        self::assertTrue($this->converter->canConvertFrom('myString', 'string'));
    }

    #[Test]
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        self::assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }

    public static function arrayToStringDataProvider(): \Iterator
    {
        yield [['Foo', 'Bar', 'Baz'], 'Foo,Bar,Baz', []];
        yield [['Foo', 'Bar', 'Baz'], 'Foo, Bar, Baz', [StringConverter::CONFIGURATION_CSV_DELIMITER => ', ']];
        yield [[], '', []];
        yield [[1,2, 'foo'], '[1,2,"foo"]', [StringConverter::CONFIGURATION_ARRAY_FORMAT => StringConverter::ARRAY_FORMAT_JSON]];
    }

    #[DataProvider('arrayToStringDataProvider')]
    #[Test]
    public function canConvertFromStringToArray($source, $expectedResult, $mappingConfiguration)
    {
        // Create a map of arguments to return values.
        $configurationValueMap = [];
        foreach ($mappingConfiguration as $setting => $value) {
            $configurationValueMap[] = [StringConverter::class, $setting, $value];
        }

        $propertyMappingConfiguration = $this->createMock(PropertyMappingConfiguration::class);
        $propertyMappingConfiguration
            ->method('getConfigurationValue')
            ->willReturnMap($configurationValueMap);

        self::assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', [], $propertyMappingConfiguration));
    }
}
