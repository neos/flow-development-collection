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

use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\StringConverter;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the String converter
 *
 * @covers \Neos\Flow\Property\TypeConverter\StringConverter<extended>
 */
class StringConverterTest extends UnitTestCase
{
    /**
     * @var \Neos\Flow\Property\TypeConverterInterface
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = new StringConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(array('string', 'integer', 'float', 'boolean', 'array', \DateTimeInterface::class), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('string', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromShouldReturnSourceString()
    {
        $this->assertEquals('myString', $this->converter->convertFrom('myString', 'string'));
    }

    /**
     * @test
     */
    public function convertFromConvertsDateTimeObjects()
    {
        $date = new \DateTime('1980-12-13');
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverterOption(StringConverter::class, StringConverter::CONFIGURATION_DATE_FORMAT, 'd.m.Y');
        $this->assertEquals('13.12.1980', $this->converter->convertFrom($date, 'string', [], $propertyMappingConfiguration));
    }

    /**
     * @test
     */
    public function convertFromConvertsDateTimeImmutableObjects()
    {
        $date = new \DateTimeImmutable('1980-12-13');
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverterOption(StringConverter::class, StringConverter::CONFIGURATION_DATE_FORMAT, 'd.m.Y');
        $this->assertEquals('13.12.1980', $this->converter->convertFrom($date, 'string', [], $propertyMappingConfiguration));
    }


    /**
     * @test
     */
    public function canConvertFromShouldReturnTrue()
    {
        $this->assertTrue($this->converter->canConvertFrom('myString', 'string'));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        $this->assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }


    public function arrayToStringDataProvider()
    {
        return [
            [['Foo', 'Bar', 'Baz'], 'Foo,Bar,Baz', []],
            [['Foo', 'Bar', 'Baz'], 'Foo, Bar, Baz', [StringConverter::CONFIGURATION_CSV_DELIMITER => ', ']],
            [[], '', []],
            [[1,2, 'foo'], '[1,2,"foo"]', [StringConverter::CONFIGURATION_ARRAY_FORMAT => StringConverter::ARRAY_FORMAT_JSON]]
        ];
    }

    /**
     * @test
     * @dataProvider arrayToStringDataProvider
     */
    public function canConvertFromStringToArray($source, $expectedResult, $mappingConfiguration)
    {

        // Create a map of arguments to return values.
        $configurationValueMap = [];
        foreach ($mappingConfiguration as $setting => $value) {
            $configurationValueMap[] = [StringConverter::class, $setting, $value];
        }

        $propertyMappingConfiguration = $this->createMock(PropertyMappingConfiguration::class);
        $propertyMappingConfiguration
            ->expects($this->any())
            ->method('getConfigurationValue')
            ->will($this->returnValueMap($configurationValueMap));

        $this->assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', [], $propertyMappingConfiguration));
    }
}
