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
use Neos\Flow\Property\TypeConverter\ArrayConverter;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\ResourceManagement\PersistentResource;

/**
 * Testcase for the Array converter
 */
class ArrayConverterTest extends UnitTestCase
{
    /**
     * @var ArrayConverter
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = new ArrayConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['array', 'string', PersistentResource::class], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromDoesNotModifyTheSourceArray()
    {
        $sourceArray = ['Foo' => 'Bar', 'Baz'];
        $this->assertEquals($sourceArray, $this->converter->convertFrom($sourceArray, 'array'));
    }

    public function stringToArrayDataProvider()
    {
        return [
            ['Foo,Bar,Baz', ['Foo', 'Bar', 'Baz'], []],
            ['Foo, Bar, Baz', ['Foo', 'Bar', 'Baz'], [ArrayConverter::CONFIGURATION_STRING_DELIMITER => ', ']],
            ['', [], []],
            ['[1,2,"foo"]', [1,2, 'foo'], [ArrayConverter::CONFIGURATION_STRING_FORMAT => ArrayConverter::STRING_FORMAT_JSON]]
        ];
    }

    /**
     * @test
     * @dataProvider stringToArrayDataProvider
     */
    public function canConvertFromStringToArray($source, $expectedResult, $mappingConfiguration)
    {

        // Create a map of arguments to return values.
        $configurationValueMap = [];
        foreach ($mappingConfiguration as $setting => $value) {
            $configurationValueMap[] = [ArrayConverter::class, $setting, $value];
        }

        $propertyMappingConfiguration = $this->createMock(PropertyMappingConfiguration::class);
        $propertyMappingConfiguration
            ->expects($this->any())
            ->method('getConfigurationValue')
            ->will($this->returnValueMap($configurationValueMap));

        $this->assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', [], $propertyMappingConfiguration));
    }
}
